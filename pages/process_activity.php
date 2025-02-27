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
    $user_id = $_SESSION['user_id'];  // สมมติว่าผู้ใช้ล็อกอินแล้ว
    $activity_date = $_POST['activity_date'];
    $post_local = $_POST['post_local'];

    // แทรกข้อมูลโพสต์ลงในตาราง community_posts
    $sql = "INSERT INTO community_posts (title, description, user_id, activity_date, created_at, updated_at, post_local) 
            VALUES (?, ?, ?, ?, NOW(), NOW(), ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssiss', $title, $description, $user_id, $activity_date, $post_local);
    $result = $stmt->execute();

    if (!$result) {
        echo "Error: " . $stmt->error; // แสดงข้อผิดพลาดหากมี
    }

    // รับ post_id ของโพสต์ที่เพิ่งถูกแทรก
    $post_id = $conn->insert_id;

    // แทรกข้อมูลกิจกรรมที่เลือกลงในตาราง post_interests
    if (isset($_POST['interests'])) {  // ตรวจสอบว่ามีการเลือกความสนใจหรือไม่
        foreach ($_POST['interests'] as $interest_id) {
            $sql = "INSERT INTO post_interests (post_id, interest_id) 
                    VALUES (?, ?)";
            $interest_stmt = $conn->prepare($sql);
            $interest_stmt->bind_param('ii', $post_id, $interest_id);
            if (!$interest_stmt->execute()) {
                echo "Error: " . $interest_stmt->error; // แสดงข้อผิดพลาดหากมี
            }
        }
    }

    // เพิ่มข้อความแจ้งเตือนหรือดำเนินการต่อหลังจากบันทึกเสร็จ
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'โพสต์และกิจกรรมถูกสร้างเรียบร้อยแล้ว!'
    ];
    header("Location: index.php");  // เปลี่ยนไปที่หน้าหลังการสร้างโพสต์
    exit();
}
