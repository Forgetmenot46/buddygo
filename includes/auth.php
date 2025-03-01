<?php
// ลบ session_start() ออก
// session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../pages/login.php");
        exit();
    }
}

function regenerateSession() {
    $session_age = 1800; // 30 minutes
    
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > $session_age) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
}

function isFirstLogin() {
    return !isset($_SESSION['first_login']);
}

if (isLoggedIn() && isFirstLogin()) {
    $_SESSION['first_login'] = true;
}