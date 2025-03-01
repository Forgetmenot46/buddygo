<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// ในส่วนการยอมรับคำขอเข้าร่วม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_id = $_POST['notification_id'];
    $action = $_POST['action'];

    // ดึงข้อมูลการแจ้งเตือนและโพสต์
    $get_info_sql = "SELECT n.*, p.title, p.image_path, p.description, p.post_id 
                     FROM notifications n 
                     JOIN community_posts p ON n.post_id = p.post_id 
                     WHERE n.id = ?";
    $info_stmt = $conn->prepare($get_info_sql);
    $info_stmt->bind_param("i", $notification_id);
    $info_stmt->execute();
    $notification_info = $info_stmt->get_result()->fetch_assoc();

    if ($action === 'accept') {
        try {
            $conn->begin_transaction();

            // อัพเดตสถานะการเข้าร่วม
            $update_member = "UPDATE post_members 
                            SET status = 'joined' 
                            WHERE post_id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_member);
            $update_stmt->bind_param("ii", 
                                  $notification_info['post_id'], 
                                  $notification_info['from_user_id']);
            $update_stmt->execute();

            // อัพเดตสถานะการแจ้งเตือน
            $update_notification = "UPDATE notifications 
                                  SET status = 'accepted', is_read = 1 
                                  WHERE id = ?";
            $update_notif_stmt = $conn->prepare($update_notification);
            $update_notif_stmt->bind_param("i", $notification_id);
            $update_notif_stmt->execute();

            // สร้างการแจ้งเตือนใหม่พร้อมรูปภาพ
            $notification_message = "คำขอเข้าร่วมกิจกรรม \"{$notification_info['title']}\" ได้รับการยอมรับแล้ว";
            $insert_notification = "INSERT INTO notifications 
                                  (user_id, from_user_id, post_id, type, message, image_path) 
                                  VALUES (?, ?, ?, 'request_accepted', ?, ?)";
            $notify_stmt = $conn->prepare($insert_notification);
            $notify_stmt->bind_param("iiiss", 
                                   $notification_info['from_user_id'],
                                   $_SESSION['user_id'],
                                   $notification_info['post_id'],
                                   $notification_message,
                                   $notification_info['image_path']);
            $notify_stmt->execute();

            $conn->commit();
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'ยอมรับคำขอเข้าร่วมเรียบร้อยแล้ว'
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    } elseif ($action === 'reject') {
        // ... โค้ดสำหรับการปฏิเสธ ...
    }
}

header('Location: notifications.php');
exit(); 