<?php
require_once 'includes/functions.php';

// Unset all session values
$_SESSION = array();

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start a new session for the flash message
session_start();
set_flash('success', 'Đăng xuất thành công');
redirect('index.php');

