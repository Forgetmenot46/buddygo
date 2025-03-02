<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ']);
    exit();
}

// ตรวจสอบข้อมูลที่ส่งมา
if (!isset($_POST['reported_user_id']) || !isset($_POST['violation_type']) || !isset($_POST['description']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

$reporting_user_id = $_SESSION['user_id'];
$reported_user_id = $_POST['reported_user_id'];
$post_id = $_POST['post_id'];
$violation_type = $_POST['violation_type'];
$description = $_POST['description'];

// ตรวจสอบว่าไม่ได้รายงานตัวเอง
if ($reporting_user_id == $reported_user_id) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถรายงานตัวเองได้']);
    exit();
}

// เพิ่มรายงานลงในฐานข้อมูล
$insert_sql = "INSERT INTO user_reports (reporting_user_id, reported_user_id, post_id, violation_type, description, status) 
               VALUES (?, ?, ?, ?, ?, 'pending')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iiiss", $reporting_user_id, $reported_user_id, $post_id, $violation_type, $description);

if ($insert_stmt->execute()) {
    // สร้างการแจ้งเตือนสำหรับแอดมิน
    $admin_sql = "SELECT id FROM users WHERE role = 'admin'";
    $admin_result = $conn->query($admin_sql);
    
    while ($admin = $admin_result->fetch_assoc()) {
        $notification_sql = "INSERT INTO notifications (user_id, from_user_id, type, message, post_id) 
                           VALUES (?, ?, 'user_report', ?, ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        
        // ดึงชื่อผู้ใช้ที่ถูกรายงาน
        $user_sql = "SELECT username FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $reported_user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $reported_user = $user_result->fetch_assoc();
        
        $message = "มีการรายงานผู้ใช้ " . $reported_user['username'] . " เนื่องจาก: " . getViolationTypeText($violation_type);
        $notification_stmt->bind_param("iisi", $admin['id'], $reporting_user_id, $message, $post_id);
        $notification_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'รายงานถูกส่งเรียบร้อยแล้ว']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกรายงาน']);
}

// ฟังก์ชันแปลงประเภทการรายงานเป็นข้อความภาษาไทย
function getViolationTypeText($type) {
    switch($type) {
        case 'no_show': return 'ไม่มาตามนัด';
        case 'harassment': return 'การคุกคาม/ก่อกวน';
        case 'inappropriate': return 'พฤติกรรมไม่เหมาะสม';
        case 'scam': return 'การหลอกลวง';
        default: return 'อื่นๆ';
    }
} 