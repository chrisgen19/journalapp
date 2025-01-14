<?php
// journal.php

require_once 'vendor/autoload.php'; // For Parsedown
use Parsedown;

class Journal {
    private $conn;
    private $parsedown;

    public function __construct($db) {
        $this->conn = $db;
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(true);
    }

    // Create new journal entry
    public function create($user_id, $title, $content, $entry_date, $images = []) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO journals (user_id, title, content, entry_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $title, $content, $entry_date);
            
            if ($stmt->execute()) {
                $journal_id = $this->conn->insert_id;
                
                // Handle image uploads if any
                if (!empty($images['tmp_name'][0])) {
                    $this->saveImages($journal_id, $images, $title);
                }
                
                return $journal_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Journal creation error: " . $e->getMessage());
            return false;
        }
    }

    // Read journal entry
    public function read($id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT j.*, GROUP_CONCAT(ji.image_path) as images 
            FROM journals j 
            LEFT JOIN journal_images ji ON j.id = ji.journal_id 
            WHERE j.id = ? AND j.user_id = ? 
            GROUP BY j.id
        ");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Convert Markdown to HTML
            $row['content_html'] = $this->parsedown->text($row['content']);
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            return $row;
        }
        return false;
    }

    // Update journal entry
    public function update($id, $user_id, $title, $content, $entry_date, $images = []) {
        $stmt = $this->conn->prepare("UPDATE journals SET title = ?, content = ?, entry_date = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $title, $content, $entry_date, $id, $user_id);
        
        if ($stmt->execute()) {
            // Handle new image uploads
            if (!empty($images)) {
                $this->saveImages($id, $images);
            }
            return true;
        }
        return false;
    }

    // Delete journal entry
    public function delete($id, $user_id) {
        try {
            // First, get all image paths for this journal
            $stmt = $this->conn->prepare("SELECT image_path FROM journal_images WHERE journal_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Delete physical image files
            while ($row = $result->fetch_assoc()) {
                $image_path = $row['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
    
            // Start transaction
            $this->conn->begin_transaction();
    
            // Delete images from journal_images table
            $stmt = $this->conn->prepare("DELETE FROM journal_images WHERE journal_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
    
            // Delete the journal entry
            $stmt = $this->conn->prepare("DELETE FROM journals WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
    
            // Commit transaction
            $this->conn->commit();
            return true;
    
        } catch (Exception $e) {
            // If any error occurs, rollback the changes
            $this->conn->rollback();
            error_log("Journal deletion error: " . $e->getMessage());
            return false;
        }
    }

    // Get all journal entries for a user
    public function getAllForUser($user_id) {
        $stmt = $this->conn->prepare("
            SELECT j.*, COUNT(ji.id) as image_count 
            FROM journals j 
            LEFT JOIN journal_images ji ON j.id = ji.journal_id 
            WHERE j.user_id = ? 
            GROUP BY j.id 
            ORDER BY j.entry_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    private function compressImage($source, $destination, $quality = 70) {
        $info = getimagesize($source);
    
        // Read EXIF data before creating image
        $exif = @exif_read_data($source);
        
        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } else {
            return false;
        }
    
        // Fix image orientation based on EXIF data
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    
        // Get dimensions after rotation
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calculate new dimensions (maintain aspect ratio)
        $max_width = 1920;
        $max_height = 1080;
        
        $ratio = min($max_width/$width, $max_height/$height);
        
        // Only resize if the image is larger than max dimensions
        if ($ratio < 1) {
            $new_width = floor($width * $ratio);
            $new_height = floor($height * $ratio);
    
            $new_image = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG
            if ($info['mime'] == 'image/png') {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
            }
            
            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $new_image;
        }
    
        // Save the compressed image
        if ($info['mime'] == 'image/jpeg') {
            imagejpeg($image, $destination, $quality);
        } else {
            imagesavealpha($image, true);
            imagepng($image, $destination, 8);
        }
    
        imagedestroy($image);
        return true;
    }

    // Save uploaded images
    private function saveImages($journal_id, $images, $title) {
        $upload_dir = 'uploads/journals/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
    
        // Process title for filename
        $title_words = explode(' ', strtolower($title));
        $title_words = array_slice($title_words, 0, 3); // Get first 3 words
        $title_part = implode('_', $title_words);
        // Remove special characters and keep only alphanumeric and underscores
        $title_part = preg_replace('/[^a-z0-9_]/', '', $title_part);
    
        $stmt = $this->conn->prepare("INSERT INTO journal_images (journal_id, image_path) VALUES (?, ?)");
        
        foreach ($images['tmp_name'] as $key => $tmp_name) {
            // Get original file extension
            $file_extension = strtolower(pathinfo($images['name'][$key], PATHINFO_EXTENSION));
            
            // Create filename: journal_id + title + index + timestamp
            $file_name = sprintf(
                'journal_%d_%s_%d_%s.%s',
                $journal_id,
                $title_part,
                $key + 1,
                date('YmdHis'),
                $file_extension
            );
            
            $file_path = $upload_dir . $file_name;
            
            // Compress and save the image
            if ($this->compressImage($tmp_name, $file_path)) {
                $stmt->bind_param("is", $journal_id, $file_path);
                $stmt->execute();
            }
        }
    }

    // Delete images associated with a journal entry
    private function deleteImages($journal_id) {
        $stmt = $this->conn->prepare("SELECT image_path FROM journal_images WHERE journal_id = ?");
        $stmt->bind_param("i", $journal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
        }
        
        $stmt = $this->conn->prepare("DELETE FROM journal_images WHERE journal_id = ?");
        $stmt->bind_param("i", $journal_id);
        $stmt->execute();
    }
}
?>