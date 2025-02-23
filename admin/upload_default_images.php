<?php
require_once '../config/config.php';

$default_images = [
    'default1.png',
    'default2.png',
    'default3.png'
];

foreach ($default_images as $image_name) {
    $image_path = "../assets/images/default_profiles/" . $image_name;
    
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        $image_type = 'image/png';
        
        // ตรวจสอบว่ามีรูปนี้ในฐานข้อมูลหรือยัง
        $check = $conn->prepare("SELECT id FROM system_images WHERE image_name = ?");
        $check->bind_param("s", $image_name);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            // ถ้ายังไม่มีให้เพิ่มเข้าไป
            $stmt = $conn->prepare("INSERT INTO system_images (image_name, image_blob, image_type) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $image_name, $image_data, $image_type);
            
            if ($stmt->execute()) {
                echo "Uploaded " . $image_name . " successfully<br>";
            } else {
                echo "Error uploading " . $image_name . "<br>";
            }
        } else {
            echo $image_name . " already exists<br>";
        }
    } else {
        echo "File " . $image_path . " not found<br>";
    }
}

echo "Done!";
?> 