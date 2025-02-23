<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messages.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = (int)$_POST['group_id'];
$message = trim($_POST['message']);

if (!empty($message)) {
    // ตรวจสอบว่าผู้ใช้เป็นสมาชิกในกลุ่ม
    $check_sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $group_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // ส่งข้อความ
        $sql = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $group_id, $user_id, $message);
        $stmt->execute();
    }
}

header("Location: messages.php?group_id=" . $group_id);
exit();
