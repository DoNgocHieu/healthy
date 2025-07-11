<?php
// Test admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['fullname'] = 'Admin Test';

// Redirect to settings page
header('Location: http://localhost/healthy/views/layout.php?page=admin&section=settings');
?>
