<!-- logout.php -->
<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth($conn);
$auth->logout();
header("Location: index.php");
exit();
?>