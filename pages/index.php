<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// เชื่อมโยง header
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
</head>


<body >
    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Section -->
            <div class="card mb-4 mb-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img src="../uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>"
                            alt="Profile"
                            class="rounded-circle"
                            style="width: 60px; height: 60px; object-fit: cover;">
                    </div>
                    <div class="col">
                        <h1 class="mb-0 mb-3">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                        <p class="text-muted mb-0">Here's what's happening with your activities</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <?php include '../includes/footer.php'; ?>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>