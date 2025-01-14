<?php
// delete_image.php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';

$auth = new Auth($conn);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$journalId = $data['journal_id'] ?? 0;
$imagePath = $data['image_path'] ?? '';

try {
    // Verify that the user owns this journal
    $stmt = $conn->prepare("SELECT user_id FROM journals WHERE id = ?");
    $stmt->bind_param("i", $journalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $journal = $result->fetch_assoc();

    if (!$journal || $journal['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }

    // Delete the image record from database
    $stmt = $conn->prepare("DELETE FROM journal_images WHERE journal_id = ? AND image_path = ?");
    $stmt->bind_param("is", $journalId, $imagePath);
    
    if ($stmt->execute()) {
        // Delete the physical file
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

} catch (Exception $e) {
    error_log("Image deletion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>