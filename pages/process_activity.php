<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $activity_date = $_POST['activity_date'];
    $activity_time = $_POST['activity_time'];
    $max_members = $_POST['max_members'];
    $interests = isset($_POST['interests']) ? $_POST['interests'] : [];

    // จัดการอัพโหลดรูปภาพ
    $image_path = null;
    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] == 0) {
        $upload_dir = "../uploads/activity_images/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['activity_image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['activity_image']['tmp_name'], $upload_path)) {
            $image_path = $new_filename;
        }
    }

    try {
        // เริ่ม transaction
        $conn->begin_transaction();

        // เพิ่มโพสต์
        $sql = "INSERT INTO community_posts (title, description, activity_date, activity_time, 
                max_members, user_id, image_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiss", $title, $description, $activity_date, 
                         $activity_time, $max_members, $user_id, $image_path);
        $stmt->execute();
        
        $post_id = $conn->insert_id;

        // เพิ่มคนสร้างเป็นสมาชิกอัตโนมัติ
        $join_sql = "INSERT INTO post_members (post_id, user_id, status) VALUES (?, ?, 'joined')";
        $join_stmt = $conn->prepare($join_sql);
        $join_stmt->bind_param("ii", $post_id, $user_id);
        $join_stmt->execute();

        // เพิ่ม interests
        if (!empty($interests)) {
            $interest_sql = "INSERT INTO post_interests (post_id, interest_id) VALUES (?, ?)";
            $interest_stmt = $conn->prepare($interest_sql);
            foreach ($interests as $interest_id) {
                $interest_stmt->bind_param("ii", $post_id, $interest_id);
                $interest_stmt->execute();
            }
        }

        $conn->commit();
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'สร้างกิจกรรมสำเร็จ'
        ];
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // ลบรูปภาพที่อัพโหลดถ้ามีข้อผิดพลาด
        if ($image_path && file_exists($upload_path)) {
            unlink($upload_path);
        }
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
        header('Location: Create_Activity.php');
        exit();
    }
}
?> 