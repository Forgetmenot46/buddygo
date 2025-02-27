<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // ตรวจสอบว่าเป็นเจ้าของโพสต์
        $check_sql = "SELECT user_id, image_path FROM community_posts WHERE post_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $post_id);
        $check_stmt->execute();
        $post = $check_stmt->get_result()->fetch_assoc();

        if ($post && $post['user_id'] == $user_id) {
            $conn->begin_transaction();

            // ลบรูปภาพถ้ามี
            if ($post['image_path']) {
                $image_path = "../uploads/activity_images/" . $post['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // ลบข้อมูลที่เกี่ยวข้อง
            $tables = ['post_members', 'post_interests', 'notifications', 'post_locations'];
            foreach ($tables as $table) {
                $delete_sql = "DELETE FROM $table WHERE post_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $post_id);
                $delete_stmt->execute();
            }

            // ลบโพสต์
            $delete_post = "DELETE FROM community_posts WHERE post_id = ?";
            $delete_stmt = $conn->prepare($delete_post);
            $delete_stmt->bind_param("i", $post_id);
            $delete_stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'คำขอไม่ถูกต้อง']);
}
?> 