<?php
session_start();
require_once '../config/config.php';

if (!isset($_GET['post_id'])) {
    header('Location: index.php');
    exit;
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่าเป็นเจ้าของโพสต์หรือไม่
$check_sql = "SELECT user_id FROM community_posts WHERE post_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้'];
    header('Location: index.php');
    exit;
}

try {
    $conn->begin_transaction();

    // ลบข้อมูลที่เกี่ยวข้องทั้งหมด
    $tables = ['post_members', 'post_interests', 'popular_activities'];
    foreach ($tables as $table) {
        $delete_sql = "DELETE FROM $table WHERE post_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $post_id);
        $delete_stmt->execute();
    }

    // ลบโพสต์หลัก
    $delete_post_sql = "DELETE FROM community_posts WHERE post_id = ?";
    $delete_post_stmt = $conn->prepare($delete_post_sql);
    $delete_post_stmt->bind_param("i", $post_id);
    $delete_post_stmt->execute();

    $conn->commit();
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'ลบโพสต์สำเร็จ'];
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'เกิดข้อผิดพลาดในการลบโพสต์'];
}

header('Location: index.php');
exit;
?> 