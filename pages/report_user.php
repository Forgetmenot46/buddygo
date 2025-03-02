<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามีการ login หรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนรายงานผู้ใช้']);
    exit();
}

// ตรวจสอบว่ามีข้อมูลที่จำเป็นครบถ้วนหรือไม่
if (!isset($_POST['reported_user_id']) || !isset($_POST['violation_type']) || !isset($_POST['description'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit();
}

$reporting_user_id = $_SESSION['user_id'];
$reported_user_id = $_POST['reported_user_id'];
$post_id = $_POST['post_id'];
$violation_type = $_POST['violation_type'];
$description = $_POST['description'];

// เพิ่มข้อมูลการรายงานลงในฐานข้อมูล
$sql = "INSERT INTO user_reports (reporting_user_id, reported_user_id, post_id, violation_type, description, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiss", $reporting_user_id, $reported_user_id, $post_id, $violation_type, $description);

if ($stmt->execute()) {
    // สร้างการแจ้งเตือนสำหรับผู้ดูแลระบบ
    $admin_notification_sql = "INSERT INTO notifications (user_id, from_user_id, type, message, status) 
                             SELECT id, ?, 'user_report', ?, 'unread'
                             FROM users WHERE role = 'admin'";
    $notification_message = "มีการรายงานผู้ใช้ใหม่: " . $violation_type;
    $stmt_notification = $conn->prepare($admin_notification_sql);
    $stmt_notification->bind_param("is", $reporting_user_id, $notification_message);
    $stmt_notification->execute();

    echo json_encode(['success' => true, 'message' => 'รายงานถูกส่งเรียบร้อยแล้ว']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกรายงาน']);
} 