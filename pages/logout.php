<?php
session_start();

// Clear remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    require_once '../config/config.php';
    
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
