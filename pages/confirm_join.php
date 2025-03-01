<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

$user_id = $_POST['user_id'];
$post_id = $_POST['post_id'];

// ตรวจสอบว่าผู้ใช้มีสิทธิ์ยืนยันหรือไม่
if ($_SESSION['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้']);
    exit();
}

// ดึงข้อมูลกิจกรรม
$post_sql = "SELECT title FROM community_posts WHERE post_id = ?";
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result()->fetch_assoc();

// ตรวจสอบว่ากิจกรรมยังรับสมาชิกได้อยู่หรือไม่
$check_sql = "SELECT current_members, max_members FROM community_posts WHERE post_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result()->fetch_assoc();

if ($result['current_members'] >= $result['max_members']) {
    echo json_encode(['success' => false, 'message' => 'กิจกรรมเต็มแล้ว']);
    exit();
}

// อัพเดทสถานะเป็น confirmed
$update_sql = "UPDATE post_members SET status = 'confirmed' WHERE post_id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $post_id, $user_id);

if ($update_stmt->execute()) {
    // อัพเดทจำนวนผู้เข้าร่วม
    $update_count_sql = "UPDATE community_posts 
                        SET current_members = (
                            SELECT COUNT(*) 
                            FROM post_members 
                            WHERE post_id = ? AND status = 'confirmed'
                        )
                        WHERE post_id = ?";
    $update_count_stmt = $conn->prepare($update_count_sql);
    $update_count_stmt->bind_param("ii", $post_id, $post_id);
    $update_count_stmt->execute();

    // สร้างการแจ้งเตือนถึงผู้สร้างกิจกรรม
    $notification_sql = "INSERT INTO notifications (user_id, from_user_id, post_id, type, message, created_at) 
                        VALUES ((SELECT user_id FROM community_posts WHERE post_id = ?), 
                        ?, ?, 'request_confirmed', 
                        (SELECT CONCAT(username, ' ได้ยืนยันการเข้าร่วมกิจกรรม ', ?) FROM users WHERE id = ?), 
                        NOW())";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param("iiiss", $post_id, $user_id, $post_id, $post_result['title'], $user_id);
    $notification_stmt->execute();

    // อัพเดทสถานะการแจ้งเตือนเดิม
    $update_notification_sql = "UPDATE notifications 
                              SET status = 'confirmed' 
                              WHERE post_id = ? AND from_user_id = ? AND type = 'join_request'";
    $update_notification_stmt = $conn->prepare($update_notification_sql);
    $update_notification_stmt->bind_param("ii", $post_id, $user_id);
    $update_notification_stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการยืนยันการเข้าร่วม']);
}

$conn->close();
?> 