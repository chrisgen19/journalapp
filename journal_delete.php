<?php
// journal_delete.php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';

$auth = new Auth($conn);
$journal = new Journal($conn);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    if ($journal->delete($id, $_SESSION['user_id'])) {
        $_SESSION['message'] = "Journal entry was successfully deleted.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete journal entry.";
        $_SESSION['message_type'] = "danger";
    }
}

// Redirect back to the page they came from, or journals.php by default
$redirect_to = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'journals.php';
header("Location: " . $redirect_to);
exit();
?>