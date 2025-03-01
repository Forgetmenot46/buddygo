<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามีการส่ง post_id มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];

    // เพิ่มจำนวนการเข้าชม
    $sql = "UPDATE community_posts SET view_count = view_count + 1 WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพเดทจำนวนการเข้าชม']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล post_id']);
}
