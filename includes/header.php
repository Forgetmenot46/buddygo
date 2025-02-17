<head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/headerstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php
// ตรวจสอบว่ามี session เปิดอยู่แล้วหรือไม่ก่อนเรียก session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เช็คว่าผู้ใช้ล็อกอินหรือยัง
$isLoggedIn = isset($_SESSION['user_id']); // ถ้ามี user_id แสดงว่า login แล้ว
?>

<!-- Sidebar -->
<aside class="sidebar">
    <header class="sidebar-header" >
        <a href="../pages/index.php" class="header-logo" >
            <img id="sidebar-logo" src="../assets/images/logo3.png" style="width : 250px" alt="BuddyGo">
        </a>
    </header>
    <nav class="sidebar-nav">
        <ul class="nav-list primary-nav">
            <li class="nav-item">
                <a href="../pages/index.php" class="nav-link">
                    <span class="material-symbols-rounded">dashboard</span>
                    <span class="nav-label">Home</span>
                </a>
            </li>
            

            <?php if ($isLoggedIn) : ?>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-rounded">edit_square</span>
                        <span class="nav-label">Create Activity</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="nav-label">Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="material-symbols-rounded">chat</span>
                        <span class="nav-label">Messages</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../pages/profile.php" class="nav-link">
                        <span class="material-symbols-rounded">account_circle</span>
                        <span class="nav-label">My Profile</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- เมนูล่าง -->
        <ul class="nav-list secondary-nav">
            <?php if ($isLoggedIn) : ?>
                <li class="nav-item">
                    <a href="../pages/settings.php" class="nav-link">
                        <span class="material-symbols-rounded">settings</span>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link" onclick="return confirmLogout(event)">
                        <span class="material-symbols-rounded">logout</span>
                        <span class="nav-label">Logout</span>
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
            <img id="mobile-logo" src="../assets/images/logo3.png" style="width : 150px"alt="BuddyGo">
        </a>
        <div class="navbar-nav gap-3">
            <li class="nav-item">
                <a class="nav-link text-white" href="../pages/index.php">
                    <span class="material-symbols-rounded">dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="#">
                    <span class="material-symbols-rounded">notifications</span>
                </a>
            </li>
            <?php if ($isLoggedIn) : ?>

                <li class="nav-item">
                    <a class="nav-link text-white" href="#">
                        <span class="material-symbols-rounded">chat</span>
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