<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามีการส่ง POST request และมี user_id
if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// ตรวจสอบว่าผู้ใช้เป็นเจ้าของโพสต์
$check_owner_sql = "SELECT user_id FROM community_posts WHERE post_id = ?";
$check_stmt = $conn->prepare($check_owner_sql);
$check_stmt->bind_param("i", $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้']);
    exit();
}

// เริ่ม transaction
$conn->begin_transaction();

try {
    // ลบข้อมูลที่เกี่ยวข้องทั้งหมด
    
    // 1. ลบการแจ้งเตือนที่เกี่ยวข้อง
    $delete_notifications = "DELETE FROM notifications WHERE post_id = ?";
    $stmt = $conn->prepare($delete_notifications);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // 2. ลบความสนใจในโพสต์
    $delete_interests = "DELETE FROM post_interests WHERE post_id = ?";
    $stmt = $conn->prepare($delete_interests);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // 3. ลบสมาชิกในโพสต์
    $delete_members = "DELETE FROM post_members WHERE post_id = ?";
    $stmt = $conn->prepare($delete_members);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // 4. ลบโพสต์
    $delete_post = "DELETE FROM community_posts WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_post);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();

    // ยืนยัน transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'ลบโพสต์เรียบร้อยแล้ว']);

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ย้อนกลับ transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบโพสต์: ' . $e->getMessage()]);
}

$conn->close();
?>
