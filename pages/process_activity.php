<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $title = $_POST['title'];
    $description = $_POST['description'];
    $interests = $_POST['interests'];
    $activity_date = $_POST['activity_date'];
    $activity_time = $_POST['activity_time'];
    $post_local = $_POST['post_local'];
    $max_members = $_POST['max_members'];
    $user_id = $_SESSION['user_id'];

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // แทรกข้อมูลโพสต์ลงในตาราง community_posts โดยตั้งค่า current_members เป็น 1
        $sql = "INSERT INTO community_posts (
                    title, description, user_id, activity_date, activity_time, 
                    created_at, updated_at, post_local, max_members, current_members
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    NOW(), NOW(), ?, ?, 1
                )";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssisssi', 
            $title, $description, $user_id, 
            $activity_date, $activity_time, $post_local, $max_members
        );
        $stmt->execute();

        // รับ post_id ของโพสต์ที่เพิ่งถูกแทรก
        $post_id = $conn->insert_id;

        // เพิ่มผู้สร้างเป็นผู้เข้าร่วมอัตโนมัติ
        $join_sql = "INSERT INTO post_members (post_id, user_id, status, joined_at) 
                     VALUES (?, ?, 'confirmed', NOW())";
        $join_stmt = $conn->prepare($join_sql);
        $join_stmt->bind_param("ii", $post_id, $user_id);
        $join_stmt->execute();

        // แทรกข้อมูลกิจกรรมที่เลือก
        if (isset($_POST['interests'])) {
            foreach ($_POST['interests'] as $interest_id) {
                $interest_sql = "INSERT INTO post_interests (post_id, interest_id) VALUES (?, ?)";
                $interest_stmt = $conn->prepare($interest_sql);
                $interest_stmt->bind_param('ii', $post_id, $interest_id);
                $interest_stmt->execute();
            }
        }

        // ยืนยัน transaction
        $conn->commit();

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'โพสต์และกิจกรรมถูกสร้างเรียบร้อยแล้ว!'
        ];
        header("Location: post_detail.php?post_id=" . $post_id); // ส่งไปหน้ารายละเอียดโพสต์
        exit();

    } catch (Exception $e) {
        // ถ้าเกิดข้อผิดพลาด ให้ rollback การทำงานทั้งหมด
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'เกิดข้อผิดพลาดในการสร้างกิจกรรม: ' . $e->getMessage()
        ];
        header("Location: Create_Activity.php");
        exit();
    }
}
