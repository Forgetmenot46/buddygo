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

// เริ่ม transaction
$conn->begin_transaction();

try {
    // เพิ่มผู้เข้าร่วมใหม่
    $insert_sql = "INSERT INTO post_members (post_id, user_id, status, joined_at) 
                VALUES (?, ?, 'interested', NOW())
                ON DUPLICATE KEY UPDATE status = 'interested', joined_at = NOW()";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $post_id, $user_id);
    $insert_stmt->execute();

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

    // ตรวจสอบว่ามีกลุ่มแชทสำหรับกิจกรรมนี้หรือไม่
    $check_group_sql = "SELECT group_id FROM chat_groups WHERE post_id = ?";
    $check_group_stmt = $conn->prepare($check_group_sql);
    $check_group_stmt->bind_param("i", $post_id);
    $check_group_stmt->execute();
    $group_result = $check_group_stmt->get_result();

    if ($group_result->num_rows > 0) {
        // มีกลุ่มแชทอยู่แล้ว เพิ่มผู้ใช้เข้าไปในกลุ่ม
        $group_id = $group_result->fetch_assoc()['group_id'];
        
        // ตรวจสอบว่าผู้ใช้อยู่ในกลุ่มแล้วหรือไม่
        $check_member_sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $check_member_stmt = $conn->prepare($check_member_sql);
        $check_member_stmt->bind_param("ii", $group_id, $user_id);
        $check_member_stmt->execute();
        
        if ($check_member_stmt->get_result()->num_rows == 0) {
            // ยังไม่อยู่ในกลุ่ม เพิ่มเข้าไป
            $add_member_sql = "INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 0)";
            $add_member_stmt = $conn->prepare($add_member_sql);
            $add_member_stmt->bind_param("ii", $group_id, $user_id);
            $add_member_stmt->execute();
            
            // เพิ่มข้อความต้อนรับ
            $welcome_message = $_SESSION['username'] . " ได้เข้าร่วมกลุ่มแชท";
            $add_message_sql = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)";
            $add_message_stmt = $conn->prepare($add_message_sql);
            $add_message_stmt->bind_param("iis", $group_id, $user_id, $welcome_message);
            $add_message_stmt->execute();
        }
    }
    
    // ยืนยัน transaction
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // ถ้าเกิดข้อผิดพลาด ให้ rollback การทำงานทั้งหมด
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเข้าร่วมกิจกรรม: ' . $e->getMessage()]);
}

$conn->close();
?> 