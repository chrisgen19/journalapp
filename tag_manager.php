<?php
class TagManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new tag
    public function createTag($user_id, $name) {
        $name = trim(strtolower($name));
        try {
            $stmt = $this->conn->prepare("INSERT INTO tags (user_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $name);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Tag creation error: " . $e->getMessage());
            return false;
        }
    }

    // Get all tags for a user
    public function getUserTags($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM tag_usage_stats WHERE user_id = ? ORDER BY usage_count DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Add tags to a journal entry
    // Update this method in tag_manager.php
    public function addTagsToJournal($journal_id, $tags, $user_id) {
        $this->conn->begin_transaction();
        try {
            // First clear existing tags
            $this->clearJournalTags($journal_id);
            
            foreach ($tags as $tag_name) {
                $tag_name = trim(strtolower($tag_name));
                if (empty($tag_name)) continue;
                
                // Try to get existing tag
                $stmt = $this->conn->prepare("SELECT id FROM tags WHERE user_id = ? AND name = ?");
                $stmt->bind_param("is", $user_id, $tag_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Create new tag if it doesn't exist
                    $this->createTag($user_id, $tag_name);
                    $tag_id = $this->conn->insert_id;
                } else {
                    $tag_id = $result->fetch_assoc()['id'];
                }
                
                // Add tag to journal
                $stmt = $this->conn->prepare("INSERT IGNORE INTO journal_tags (journal_id, tag_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $journal_id, $tag_id);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error adding tags: " . $e->getMessage());
            return false;
        }
    }

    public function getJournalTags($journal_id) {
        $stmt = $this->conn->prepare("
            SELECT t.* 
            FROM tags t 
            JOIN journal_tags jt ON t.id = jt.tag_id 
            WHERE jt.journal_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->bind_param("i", $journal_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function clearJournalTags($journal_id) {
        $stmt = $this->conn->prepare("DELETE FROM journal_tags WHERE journal_id = ?");
        $stmt->bind_param("i", $journal_id);
        return $stmt->execute();
    }

    // Add this method to your TagManager class
    public function getJournalsByTag($tag_id, $user_id, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT j.*, 
                (SELECT image_path FROM journal_images WHERE journal_id = j.id LIMIT 1) as thumbnail,
                (SELECT COUNT(*) FROM journal_images WHERE journal_id = j.id) as image_count
            FROM journals j 
            JOIN journal_tags jt ON j.id = jt.journal_id 
            JOIN tags t ON jt.tag_id = t.id 
            WHERE t.id = ? AND j.user_id = ? 
            ORDER BY j.entry_date DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iiii", $tag_id, $user_id, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }
}