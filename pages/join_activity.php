<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// ตรวจสอบว่ากิจกรรมยังรับสมาชิกได้อยู่หรือไม่
$check_sql = "SELECT p.*, u.id as creator_id FROM community_posts p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.post_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result()->fetch_assoc();

if ($result['current_members'] >= $result['max_members']) {
    echo json_encode(['success' => false, 'message' => 'กิจกรรมเต็มแล้ว']);
    exit();
}

// เพิ่มผู้เข้าร่วมใหม่
$insert_sql = "INSERT INTO post_members (post_id, user_id, status, joined_at) 
               VALUES (?, ?, 'interested', NOW())
               ON DUPLICATE KEY UPDATE status = 'interested', joined_at = NOW()";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ii", $post_id, $user_id);

if ($insert_stmt->execute()) {
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
                        VALUES (?, ?, ?, 'join_request', 
                        (SELECT CONCAT(username, ' สนใจเข้าร่วมกิจกรรม ', ?) FROM users WHERE id = ?), 
                        NOW())";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param("iiiss", $result['creator_id'], $user_id, $post_id, $result['title'], $user_id);
    $notification_stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเข้าร่วมกิจกรรม']);
}

$conn->close();
?> 