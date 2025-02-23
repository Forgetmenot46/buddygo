<?php
require_once '../config/config.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    
    $stmt = $conn->prepare("SELECT profile_image, profile_image_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc() && $row['profile_image']) {
        header("Content-Type: " . $row['profile_image_type']);
        echo $row['profile_image'];
    } else {
        // ส่งรูปเริ่มต้นถ้าไม่มีรูป
        header("Content-Type: image/png");
        echo file_get_contents("../assets/images/default_profiles/default1.png");
    }
}
?> 