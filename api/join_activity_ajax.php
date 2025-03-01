<?php
session_start();
require_once '../config/config.php';

// รับข้อมูล JSON
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'];
$user_id = $data['user_id'];
$status = $data['status'];
$action = $data['action'] ?? 'join';

// ตรวจสอบว่าเป็นผู้ใช้ที่ล็อกอินอยู่
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

// ตรวจสอบว่าเป็นผู้สร้างโพสต์หรือไม่
$check_creator = "SELECT user_id FROM community_posts WHERE post_id = ?";
$creator_stmt = $conn->prepare($check_creator);
$creator_stmt->bind_param("i", $post_id);
$creator_stmt->execute();
$creator_result = $creator_stmt->get_result();
$creator = $creator_result->fetch_assoc();

if ($creator && $creator['user_id'] == $user_id) {
    // ถ้าเป็นผู้สร้าง ให้เพิ่มเป็นผู้เข้าร่วมอัตโนมัติ
    $sql = "INSERT INTO post_members (post_id, user_id, status) VALUES (?, ?, 'confirmed')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    
    if ($stmt->execute()) {
        // อัพเดทจำนวนผู้เข้าร่วม
        $update_count = "UPDATE community_posts SET current_members = current_members + 1 WHERE post_id = ?";
        $count_stmt = $conn->prepare($update_count);
        $count_stmt->bind_param("i", $post_id);
        $count_stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
    }
    exit();
}

// สำหรับผู้ใช้ทั่วไป
if ($action === 'confirm') {
    $sql = "UPDATE post_members SET status = 'confirmed' WHERE post_id = ? AND user_id = ? AND status = 'interested'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
} else if ($action === 'cancel') {
    // ลบข้อมูลการเข้าร่วม
    try {
        $sql = "DELETE FROM post_members WHERE post_id = ? AND user_id = ? AND status = 'interested'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ยกเลิกการเข้าร่วมเรียบร้อยแล้ว']);
        } else {
            throw new Exception("ไม่สามารถลบข้อมูลได้");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการยกเลิกการเข้าร่วม: ' . $e->getMessage()
        ]);
    }
    exit();
} else {
    // ตรวจสอบว่าเข้าร่วมไปแล้วหรือไม่
    $check_sql = "SELECT status FROM post_members WHERE post_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'คุณได้เข้าร่วมกิจกรรมนี้แล้ว']);
        exit();
    }

    $sql = "INSERT INTO post_members (post_id, user_id, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $post_id, $user_id, $status);
}

if ($stmt->execute()) {
    if ($action === 'confirm') {
        // อัพเดทจำนวนผู้เข้าร่วมเมื่อยืนยัน
        $update_count = "UPDATE community_posts SET current_members = current_members + 1 WHERE post_id = ?";
        $count_stmt = $conn->prepare($update_count);
        $count_stmt->bind_param("i", $post_id);
        $count_stmt->execute();
        
        // สร้างการแจ้งเตือนให้เจ้าของโพสต์
        try {
            // ดึงข้อมูลโพสต์และเจ้าของโพสต์
            $post_info_sql = "SELECT p.*, u.username FROM community_posts p 
                             JOIN users u ON p.user_id = u.id 
                             WHERE p.post_id = ?";
            $post_info_stmt = $conn->prepare($post_info_sql);
            $post_info_stmt->bind_param("i", $post_id);
            $post_info_stmt->execute();
            $post_info = $post_info_stmt->get_result()->fetch_assoc();
            
            // ดึงข้อมูลผู้ใช้ที่เข้าร่วม
            $user_info_sql = "SELECT username FROM users WHERE id = ?";
            $user_info_stmt = $conn->prepare($user_info_sql);
            $user_info_stmt->bind_param("i", $user_id);
            $user_info_stmt->execute();
            $user_info = $user_info_stmt->get_result()->fetch_assoc();
            
            // สร้างข้อความแจ้งเตือน
            $notification_message = "{$user_info['username']} ได้เข้าร่วมกิจกรรม \"{$post_info['title']}\" ของคุณแล้ว";
            
            // เพิ่มการแจ้งเตือนลงในฐานข้อมูล
            $insert_notification = "INSERT INTO notifications 
                                  (user_id, from_user_id, post_id, type, message, image_path) 
                                  VALUES (?, ?, ?, 'join_request', ?, ?)";
            $notify_stmt = $conn->prepare($insert_notification);
            $notify_stmt->bind_param("iiiss", 
                                   $post_info['user_id'],
                                   $user_id,
                                   $post_id,
                                   $notification_message,
                                   $post_info['image_path']);
            $notify_stmt->execute();
        } catch (Exception $e) {
            // บันทึกข้อผิดพลาดแต่ไม่หยุดการทำงาน
            error_log("Error creating notification: " . $e->getMessage());
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
} 