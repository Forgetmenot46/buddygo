<?php
session_start();
require_once '../config/config.php';

// ตรวจสอบว่ามีการส่ง post_id มาหรือไม่
if (!isset($_GET['post_id'])) {
    header("Location: index.php");
    exit();
}

$post_id = $_GET['post_id'];

// ดึงข้อมูลโพสต์จากฐานข้อมูล
$post_sql = "SELECT p.*, u.username, u.profile_picture 
             FROM community_posts p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.post_id = ?";
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();

if ($post_result->num_rows === 0) {
    echo "ไม่พบโพสต์ที่ต้องการ";
    exit();
}

$post = $post_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - BuddyGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="d-flex align-items-center mb-3">
            <img src="<?php echo getProfileImage($post['user_id']); ?>" class="rounded-circle me-2" style="width: 40px; height: 40px;">
            <small class="text-muted">โดย <?php echo htmlspecialchars($post['username']); ?></small>
        </div>
        <p><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
        <div>
            <strong>วันที่จัดกิจกรรม:</strong> <?php echo date('d/m/Y', strtotime($post['activity_date'])); ?><br>
            <strong>เวลา:</strong> <?php echo date('H:i', strtotime($post['activity_time'])); ?> น.<br>
            <strong>จำนวนคนที่รับ:</strong> <?php echo $post['max_members']; ?> คน
        </div>
        <a href="index.php" class="btn btn-primary mt-3">กลับไปยังหน้าหลัก</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 