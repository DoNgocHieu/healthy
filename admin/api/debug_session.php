<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'user_data' => $_SESSION['user'] ?? null,
    'is_logged_in' => isset($_SESSION['user']),
    'user_role' => $_SESSION['user']['role'] ?? 'not set'
]);
?>
