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

// เริ่ม transaction
$conn->begin_transaction();

try {
    // อัพเดทสถานะเป็น confirmed
    $update_sql = "UPDATE post_members SET status = 'confirmed' WHERE post_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $post_id, $user_id);
    $update_stmt->execute();

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
        $check_member_sql = "SELECT * FROM chat_group_members WHERE group_id = ? AND user_id = ?";
        $check_member_stmt = $conn->prepare($check_member_sql);
        $check_member_stmt->bind_param("ii", $group_id, $user_id);
        $check_member_stmt->execute();
        
        if ($check_member_stmt->get_result()->num_rows == 0) {
            // ยังไม่อยู่ในกลุ่ม เพิ่มเข้าไป
            $add_member_sql = "INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)";
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
    } else {
        // ถ้ายังไม่มีกลุ่มแชท ให้สร้างใหม่
        $create_group_sql = "INSERT INTO chat_groups (post_id) VALUES (?)";
        $create_group_stmt = $conn->prepare($create_group_sql);
        $create_group_stmt->bind_param("i", $post_id);
        $create_group_stmt->execute();
        
        // รับ group_id ของกลุ่มแชทที่เพิ่งถูกสร้าง
        $group_id = $conn->insert_id;
        
        // เพิ่มผู้ใช้เป็นสมาชิกกลุ่ม
        $add_member_sql = "INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)";
        $add_member_stmt = $conn->prepare($add_member_sql);
        $add_member_stmt->bind_param("ii", $group_id, $user_id);
        $add_member_stmt->execute();
        
        // เพิ่มผู้สร้างกิจกรรมเป็นแอดมินกลุ่ม
        $creator_sql = "SELECT user_id FROM community_posts WHERE post_id = ?";
        $creator_stmt = $conn->prepare($creator_sql);
        $creator_stmt->bind_param("i", $post_id);
        $creator_stmt->execute();
        $creator_id = $creator_stmt->get_result()->fetch_assoc()['user_id'];
        
        $add_admin_sql = "INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)";
        $add_admin_stmt = $conn->prepare($add_admin_sql);
        $add_admin_stmt->bind_param("ii", $group_id, $creator_id);
        $add_admin_stmt->execute();
        
        // เพิ่มข้อความต้อนรับในกลุ่มแชท
        $welcome_message = "ยินดีต้อนรับสู่กลุ่มแชทสำหรับกิจกรรม \"" . $post_result['title'] . "\"";
        $add_message_sql = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)";
        $add_message_stmt = $conn->prepare($add_message_sql);
        $add_message_stmt->bind_param("iis", $group_id, $creator_id, $welcome_message);
        $add_message_stmt->execute();
    }
    
    // ยืนยัน transaction
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // ถ้าเกิดข้อผิดพลาด ให้ rollback การทำงานทั้งหมด
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการยืนยันการเข้าร่วม: ' . $e->getMessage()]);
}

$conn->close();
?> 