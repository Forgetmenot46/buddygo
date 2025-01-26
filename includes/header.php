<?php
// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // เริ่ม session สำหรับการเก็บข้อมูลของผู้ใช้
}

// เรียกไฟล์การเชื่อมต่อกับฐานข้อมูล
require_once '../config/config.php';

// กำหนดค่าเริ่มต้นสำหรับภาพโปรไฟล์
$profilePicture = 'default.png'; // ถ้าไม่มีภาพโปรไฟล์จะใช้ 'default.png'
$userName = null; // กำหนดชื่อผู้ใช้เริ่มต้นเป็น null

// ตรวจสอบว่าผู้ใช้ได้ล็อกอินหรือไม่
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // ดึง id ของผู้ใช้จาก session

    // สร้างคำสั่ง SQL เพื่อดึงชื่อผู้ใช้และภาพโปรไฟล์จากฐานข้อมูล
    $query = "SELECT username, profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query); // เตรียมคำสั่ง SQL
    $stmt->bind_param("i", $userId); // ผูกค่าของ user_id ไปที่ตัวแปรในคำสั่ง SQL
    $stmt->execute(); // รันคำสั่ง SQL
    $result = $stmt->get_result(); // ดึงผลลัพธ์จากฐานข้อมูล

    // ตรวจสอบว่าผลลัพธ์จากการค้นหามีข้อมูลหรือไม่
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
        $profilePicture = $user['profile_picture'] ?? 'default.png'; // ถ้าผู้ใช้มีภาพโปรไฟล์ จะใช้ภาพนั้น ถ้าไม่มีก็ใช้ 'default.png'
        $userName = $user['username']; // กำหนดชื่อผู้ใช้
    }
    $stmt->close(); // ปิดการเชื่อมต่อกับฐานข้อมูล
}
?>

<!-- แสดง Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <!-- ลิงก์ไปหน้า Home -->
        <a class="navbar-brand" href="../pages/index.php">
            <img src="../assets/images/logo1.png" alt="BuddyGo Logo" height="40">
        </a>
        <!-- ปุ่มสำหรับแสดงเมนูบนมือถือ -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- เมนูหลัก -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- ลิงก์ไปหน้า Home -->
                <li class="nav-item"><a class="nav-link" href="../pages/about.php">About</a></li>
                <!-- ลิงก์ไปหน้า Profile -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="../pages/proflie.php">Profile</a></li>
                <?php endif; ?>
                <!-- หากผู้ใช้ล็อกอินแล้ว จะมีปุ่ม Dashboard -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="../pages/settings.php">Settings</a></li>
                <?php endif; ?>
            </ul>
            <!-- ถ้าผู้ใช้ยังไม่ได้ล็อกอิน ให้แสดงปุ่ม Login -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="../pages/login.php" class="btn btn-primary">Login</a>
            <?php else: ?>
                <!-- ถ้าผู้ใช้ล็อกอินแล้ว จะแสดงภาพโปรไฟล์และชื่อผู้ใช้ พร้อมปุ่ม Logout -->
                <div class="header-profile d-flex align-items-center">
                    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="profile-img" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <span class="navbar-text mx-2">Hello, <?php echo htmlspecialchars($userName); ?>!</span>
                    <a href="../pages/logout.php" class="btn btn-danger">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>