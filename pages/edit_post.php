<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $activity_date = $_POST['activity_date'];
    $activity_time = $_POST['activity_time'];
    $max_members = $_POST['max_members'];
    $interests = isset($_POST['interests']) ? $_POST['interests'] : [];

    // ตรวจสอบว่าเป็นเจ้าของโพสต์
    $check_owner = $conn->prepare("SELECT user_id FROM community_posts WHERE post_id = ?");
    $check_owner->bind_param("i", $post_id);
    $check_owner->execute();
    $result = $check_owner->get_result()->fetch_assoc();

    if ($result['user_id'] != $_SESSION['user_id']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'คุณไม่มีสิทธิ์แก้ไขโพสต์นี้'
        ];
        header('Location: index.php');
        exit();
    }

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // อัพเดตข้อมูลโพสต์
        $update_sql = "UPDATE community_posts SET 
                      title = ?, 
                      description = ?, 
                      activity_date = ?, 
                      activity_time = ?, 
                      max_members = ?, 
                      post_location = ?, 
                      updated_at = NOW() 
                      WHERE post_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssiii", $title, $description, $activity_date, 
                                $activity_time, $max_members, $post_id);
        $update_stmt->execute();

        // อัพเดต interests
        $delete_interests = "DELETE FROM post_interests WHERE post_id = ?";
        $delete_stmt = $conn->prepare($delete_interests);
        $delete_stmt->bind_param("i", $post_id);
        $delete_stmt->execute();

        if (!empty($interests)) {
            $insert_interest = "INSERT INTO post_interests (post_id, interest_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_interest);
            foreach ($interests as $interest_id) {
                $insert_stmt->bind_param("ii", $post_id, $interest_id);
                $insert_stmt->execute();
            }
        }

        $conn->commit();
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'แก้ไขโพสต์สำเร็จ'
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