<?php
// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";  // ชื่อผู้ใช้ MySQL
$password = "";      // รหัสผ่าน MySQL (ถ้ามี)
$dbname = "buddygo";

try {
    // สร้างการเชื่อมต่อ
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // ตั้งค่า charset เป็น utf8
    $conn->set_charset("utf8");

    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

} catch(Exception $e) {
    // แสดงข้อความเมื่อเกิดข้อผิดพลาด
    die("Connection failed: " . $e->getMessage());
}

// สร้างโฟลเดอร์ที่จำเป็น
$required_directories = [
    __DIR__ . '/../assets/images/default_profiles',
    __DIR__ . '/../uploads/profile_pictures'
];

foreach ($required_directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ตรวจสอบและคัดลอกรูปเริ่มต้น
$default_images = ['default1.png', 'default2.png', 'default3.png'];
$source_dir = __DIR__ . '/../assets/images/system';
$target_dir = __DIR__ . '/../assets/images/default_profiles';

foreach ($default_images as $image) {
    $target_file = $target_dir . '/' . $image;
    if (!file_exists($target_file) && file_exists($source_dir . '/' . $image)) {
        copy($source_dir . '/' . $image, $target_file);
    }
}

?>
