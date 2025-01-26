<?php
// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";  // ชื่อผู้ใช้ MySQL
$password = "";      // รหัสผ่าน MySQL (ถ้ามี)
$dbname = "buddygodatabase";

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


?>
