<head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/headerstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<?php
// ตรวจสอบว่ามี session เปิดอยู่แล้วหรือไม่ก่อนเรียก session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เช็คว่าผู้ใช้ล็อกอินหรือยัง
$isLoggedIn = isset($_SESSION['user_id']); // ถ้ามี user_id แสดงว่า login แล้ว
?>

<!-- Loading Screen -->
<div class="loading-screen" id="loading-screen">
    <div class="loading-logo">BuddyGo</div>
    <div class="loading-spinner"></div>
    <div class="loading-text">กำลังโหลด...</div>
</div>

<!-- CSS สำหรับ Loading Screen -->
<style>
    /* Loading Screen Styles */
    .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease-out;
    }

    .loading-screen.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .loading-logo {
        margin-bottom: 20px;
        font-size: 32px;
        font-weight: bold;
        color: #0d6efd;
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 5px solid rgba(13, 110, 253, 0.2);
        border-top: 5px solid #0d6efd;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loading-text {
        margin-top: 15px;
        font-size: 16px;
        color: #6c757d;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!-- JavaScript สำหรับ Loading Screen -->
<script>
    // Loading Screen Script
    document.addEventListener('DOMContentLoaded', function() {
        const loadingScreen = document.getElementById('loading-screen');

        // ซ่อน Loading Screen หลังจากโหลดเสร็จ
        window.addEventListener('load', function() {
            // รอสักครู่ก่อนซ่อน loading screen
            setTimeout(function() {
                loadingScreen.classList.add('hidden');

                // ลบ loading screen ออกจาก DOM หลังจากการเฟดเสร็จสิ้น
                setTimeout(function() {
                    loadingScreen.remove();
                }, 500);
            }, 800); // รอ 800ms ก่อนซ่อน
        });

        // ตั้งเวลาสำรองในกรณีที่ event load ไม่ทำงาน
        setTimeout(function() {
            if (loadingScreen && !loadingScreen.classList.contains('hidden')) {
                loadingScreen.classList.add('hidden');
                setTimeout(function() {
                    loadingScreen.remove();
                }, 500);
            }
        }, 5000); // รอสูงสุด 5 วินาที
    });
</script>

<!-- Sidebar -->
<aside class="sidebar">
    <header class="sidebar-header">
        <a href="../pages/index.php" class="header-logo">
            <img id="sidebar-logo" src="../assets/images/logo3.png" style="width: 250px" alt="BuddyGo">
        </a>
    </header>
    <nav class="sidebar-nav">
        <ul class="nav-list primary-nav">
            <li class="nav-item">
                <a href="../pages/index.php" class="nav-link <?php echo isCurrentPage('index.php') ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded">dashboard</span>
                    <span class="nav-label">หน้าหลัก</span>
                </a>
            </li>

            <?php if ($isLoggedIn) : ?>
                <li class="nav-item">
                    <a href="Create_Activity.php" class="nav-link <?php echo isCurrentPage('Create_Activity.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">edit_square</span>
                        <span class="nav-label">สร้างกิจกรรม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="group_chat.php" class="nav-link <?php echo isCurrentPage('group_chat.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">groups</span>
                        <span class="nav-label">แชทกิจกรรม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="notifications.php" class="nav-link <?php echo isCurrentPage('notifications.php') ? 'active' : ''; ?> position-relative">
                        <i class="fas fa-bell"></i>
                        <span class="nav-label">แจ้งเตือน</span>
                        <?php
                        // นับจำนวนการแจ้งเตือนที่ยังไม่ได้อ่าน
                        $unread_count = 0; // กำหนดค่าเริ่มต้นเพื่อลด error undefined variable
                        $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $unread_stmt = $conn->prepare($unread_sql);
                        $unread_stmt->bind_param("i", $_SESSION['user_id']);
                        $unread_stmt->execute();
                        $result = $unread_stmt->get_result();
                        if ($result) {
                            $row = $result->fetch_assoc();
                            if ($row) {
                                $unread_count = $row['count'];
                            }
                        }
                        if ($unread_count > 0):
                        ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notification-header d-flex justify-content-between align-items-center">
                    </div>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo isCurrentPage('profile.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">person</span>
                        <span class="nav-label">โปรไฟล์ของฉัน</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- เมนูล่าง -->
        <ul class="nav-list secondary-nav">
            <?php if ($isLoggedIn) : ?>
                <li class="nav-item">
                    <a href="../pages/settings.php" class="nav-link <?php echo isCurrentPage('settings.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">settings</span>
                        <span class="nav-label">ตั้งค่า</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link" onclick="return confirmLogout(event)">
                        <span class="material-symbols-rounded">logout</span>
                        <span class="nav-label">ออกจากระบบ</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <ul class="nav-list">
            <?php if (!$isLoggedIn): ?>
                <li class="nav-item">
                    <a href="login.php" class="nav-link">
                        <span class="material-symbols-rounded">login</span>
                        <span class="nav-label">Login</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<!-- Navbar for mobile view -->
<nav class="navbar navbar-expand d-lg-none">
    <div class="container-fluid">
        <a href="../pages/index.php" class="header-logo">
            <img id="mobile-logo" src="../assets/images/logo3.png" style="width: 150px" alt="BuddyGo">
        </a>
        <div class="navbar-nav gap-3">
            <li class="nav-item">
                <a class="nav-link text-white" href="../pages/index.php">
                    <span class="material-symbols-rounded">dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="notifications.php">
                    <i class="fas fa-bell"></i>
                </a>
            </li>
            <?php if ($isLoggedIn) : ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="group_chat.php">
                        <span class="material-symbols-rounded">groups</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="notifications.php">
                        <i class="fas fa-bell"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../pages/profile.php">
                        <span class="material-symbols-rounded">account_circle</span>
                    </a>
                </li>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmLogout(event) {
        if (!confirm("คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?")) {
            event.preventDefault(); // Prevent the default action if the user cancels
        }
    }
</script>

<?php
// เพิ่มฟังก์ชันตรวจสอบหน้าปัจจุบัน
function isCurrentPage($pageName)
{
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($pageName === 'index.php' && $currentPage === 'index.php') {
        return true;
    }
    return $currentPage === $pageName;
}
?>

<style>
    /* แก้ไข CSS สำหรับ notification badge */
    .menu-item {
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .notification-badge {
        position: absolute;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        right: +20px;
        /* ปรับตำแหน่งซ้าย-ขวา */
        top: +11px;
        /* ปรับตำแหน่งบน-ล่าง */
        min-width: 1rem;
        text-align: center;
    }

    /* ถ้าตัวเลขมีหลักเดียว */
    .notification-badge:not(:empty) {
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ถ้าตัวเลขมีสองหลักขึ้นไป */
    .notification-badge[data-count]:not([data-count="0"]) {
        min-width: 2rem;
    }
</style>