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

// ตรวจสอบว่าผู้ใช้มีสิทธิ์ยกเลิกหรือไม่
if ($_SESSION['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้']);
    exit();
}

// ดึงข้อมูลกิจกรรม
$post_sql = "SELECT p.title, p.user_id as creator_id FROM community_posts p WHERE p.post_id = ?";
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result()->fetch_assoc();

// ลบการเข้าร่วมกิจกรรม
$delete_sql = "DELETE FROM post_members WHERE post_id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $post_id, $user_id);

if ($delete_stmt->execute()) {
    // อัพเดทจำนวนผู้เข้าร่วม
    $update_sql = "UPDATE community_posts 
                   SET current_members = (
                       SELECT COUNT(*) 
                       FROM post_members 
                       WHERE post_id = ? AND status IN ('interested', 'confirmed')
                   )
                   WHERE post_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $post_id, $post_id);
    $update_stmt->execute();

    // สร้างการแจ้งเตือนถึงผู้สร้างกิจกรรม
    $notification_sql = "INSERT INTO notifications (user_id, from_user_id, post_id, type, message, created_at) 
                        VALUES (?, ?, ?, 'request_cancelled', 
                        (SELECT CONCAT(username, ' ได้ยกเลิกการเข้าร่วมกิจกรรม ', ?) FROM users WHERE id = ?), 
                        NOW())";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param("iiiss", $post_result['creator_id'], $user_id, $post_id, $post_result['title'], $user_id);
    $notification_stmt->execute();

    // อัพเดทสถานะการแจ้งเตือนเดิม
    $update_notification_sql = "UPDATE notifications 
                              SET status = 'cancelled' 
                              WHERE post_id = ? AND from_user_id = ? AND type IN ('join_request', 'request_confirmed')";
    $update_notification_stmt = $conn->prepare($update_notification_sql);
    $update_notification_stmt->bind_param("ii", $post_id, $user_id);
    $update_notification_stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการยกเลิกการเข้าร่วม']);
}

$conn->close();
?> 