<?php
require_once '../config/config.php';

if (isset($_GET['name'])) {
    $image_name = $_GET['name'];
    
    // ลองหารูปจากไฟล์ก่อน
    $file_path = "../assets/images/default_profiles/" . $image_name;
    if (file_exists($file_path)) {
        $image_type = mime_content_type($file_path);
        header("Content-Type: " . $image_type);
        readfile($file_path);
        exit;
    }
    
    // ถ้าไม่มีในไฟล์ ให้หาในฐานข้อมูล
    $stmt = $conn->prepare("SELECT image_blob, image_type FROM system_images WHERE image_name = ?");
    $stmt->bind_param("s", $image_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header("Content-Type: " . $row['image_type']);
        echo $row['image_blob'];
        exit;
    }
    
    // ถ้าไม่เจอทั้งในไฟล์และฐานข้อมูล ให้ใช้รูปเริ่มต้น
    $default_path = "../assets/images/default_profiles/default1.png";
    if (file_exists($default_path)) {
        header("Content-Type: image/png");
        readfile($default_path);
    }
}
?> 