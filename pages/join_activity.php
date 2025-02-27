<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'กรุณาเข้าสู่ระบบก่อนเข้าร่วมกิจกรรม'
    ];
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'join') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // เช็คว่าเป็นสมาชิกอยู่แล้วหรือไม่
    $check_sql = "SELECT * FROM post_members WHERE post_id = ? AND user_id = ? AND post_location = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $post_id, $user_id, $post_location);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'คุณเป็นสมาชิกของกิจกรรมนี้อยู่แล้ว'
        ];
        header('Location: index.php');
        exit();
    }

    // เช็คจำนวนสมาชิกปัจจุบันและจำนวนสูงสุด
    $check_members_sql = "SELECT p.max_members, COUNT(pm.user_id) as current_members 
                         FROM community_posts p 
                         LEFT JOIN post_members pm ON p.post_id = pm.post_id 
                         WHERE p.post_id = ? 
                         GROUP BY p.post_id";
    $check_members_stmt = $conn->prepare($check_members_sql);
    $check_members_stmt->bind_param("i", $post_id);
    $check_members_stmt->execute();
    $members_result = $check_members_stmt->get_result();
    $members_data = $members_result->fetch_assoc();

    if ($members_data['current_members'] >= $members_data['max_members']) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'กิจกรรมนี้มีสมาชิกเต็มแล้ว'
        ];
        header('Location: index.php');
        exit();
    }

    // เพิ่มสมาชิกใหม่
    $conn->begin_transaction();

    try {
        // เพิ่มเป็นสมาชิกกิจกรรมในสถานะ 'pending'
        $join_sql = "INSERT INTO post_members (post_id, user_id, status) VALUES (?, ?, 'pending')";
        $join_stmt = $conn->prepare($join_sql);
        $join_stmt->bind_param("ii", $post_id, $user_id);
        $join_stmt->execute();

        // สร้างการแจ้งเตือนสำหรับเจ้าของโพสต์
        $notification_sql = "INSERT INTO notifications (user_id, from_user_id, post_id, type, message, created_at) 
                            SELECT p.user_id, ?, p.post_id, 'join_request', 
                            CONCAT((SELECT username FROM users WHERE id = ?), ' ต้องการเข้าร่วมกิจกรรม \"', p.title, '\"'),
                            NOW()
                            FROM community_posts p WHERE p.post_id = ?";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_stmt->bind_param("iii", $user_id, $user_id, $post_id);
        $notification_stmt->execute();

        $conn->commit();
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'ส่งคำขอเข้าร่วมกิจกรรมเรียบร้อยแล้ว กรุณารอการตอบรับจากผู้สร้างกิจกรรม'
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

header('Location: index.php');
exit(); 