<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // ตรวจสอบว่าเป็นเจ้าของโพสต์
        $check_sql = "SELECT user_id FROM community_posts WHERE post_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $post_id);
        $check_stmt->execute();
        $post = $check_stmt->get_result()->fetch_assoc();

        if ($post && $post['user_id'] == $user_id) {
            $conn->begin_transaction();

            // อัพเดตข้อมูลโพสต์
            $update_sql = "UPDATE community_posts SET 
                          title = ?, 
                          description = ?, 
                          activity_date = ?, 
                          activity_time = ?, 
                          max_members = ?, 
                          updated_at = NOW() 
                          WHERE post_id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssii", 
                $_POST['title'],
                $_POST['description'],
                $_POST['activity_date'],
                $_POST['activity_time'],
                $_POST['max_members'],
                $post_id
            );
            $update_stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขโพสต์นี้']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'คำขอไม่ถูกต้อง']);
}
?> 