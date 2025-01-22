<?php
session_start();

// ล้างค่า session ทั้งหมด
session_destroy();

// ล้างค่า session เฉพาะของผู้ใช้
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['firstname']);
unset($_SESSION['profile_picture']);

// สร้างข้อความแจ้งเตือน
session_start(); // เริ่ม session ใหม่เพื่อเก็บข้อความแจ้งเตือน
$_SESSION['success'] = "ออกจากระบบสำเร็จ!";

// Redirect ไปยังหน้า login
header("location: login.php");
exit();
?>
