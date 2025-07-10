<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Huỷ session
$_SESSION = [];
session_unset();
session_destroy();

header('Location: /healthy/views/layout.php?page=home');
exit;