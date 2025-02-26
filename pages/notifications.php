<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงการแจ้งเตือนทั้งหมดของผู้ใช้
$notifications_sql = "
    SELECT n.*, 
           u.username, 
           u.profile_picture,
           p.title,
           p.image_path,
           CASE 
               WHEN n.type = 'join_request' AND n.status IS NULL THEN 0
               WHEN n.status IS NOT NULL THEN 1
               ELSE 0
           END as is_processed
    FROM notifications n 
    JOIN users u ON n.from_user_id = u.id 
    LEFT JOIN community_posts p ON n.post_id = p.post_id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การแจ้งเตือน - BuddyGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/notificationstyle.css">
</head>

<body>

    <div class="row">
        <!-- คอลัมน์ซ้าย (Sidebar) -->
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <!-- คอลัมน์ขวา (Main Content) -->
        <div class="col-12 col-md-10 mt-4">
            <div class="notifications-container">
                <h2 class="mb-4">
                    <i class="fas fa-bell me-2 text-primary"></i>การแจ้งเตือน
                </h2>

                <?php if (empty($notifications)): ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash empty-icon"></i>
                        <h4>ไม่มีการแจ้งเตือน</h4>
                        <p class="text-muted">คุณยังไม่มีการแจ้งเตือนใดๆ</p>
                    </div>
                <?php else: ?>
                    <div class="notification-list">
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo getProfileImage($notification['from_user_id']); ?>"
                                        class="rounded-circle me-3" width="50" height="50" alt="Profile Picture">
                                    <div class="flex-grow-1">
                                        <div class="notification-header">
                                            <strong><?php echo htmlspecialchars($notification['username']); ?></strong>
                                            <small class="text-muted ms-2">
                                                <?php echo timeAgo($notification['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="notification-content">
                                            <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>

                                            <?php if ($notification['type'] == 'request_accepted' && !empty($notification['image_path'])): ?>
                                                <div class="notification-image mt-2">
                                                    <div class="qr-code-container">
                                                        <p class="text-muted mb-2">QR Code สำหรับเข้ากลุ่ม LINE:</p>
                                                        <img src="../uploads/activity_images/<?php echo htmlspecialchars($notification['image_path']); ?>"
                                                            class="img-fluid rounded" style="max-height: 200px;"
                                                            alt="QR Code LINE">
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($notification['type'] == 'join_request' && !$notification['is_processed']): ?>
                                                <div class="mt-2">
                                                    <form action="handle_request.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="notification_id"
                                                            value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" name="action" value="accept"
                                                            class="btn btn-success btn-sm">
                                                            <i class="fas fa-check me-1"></i>ยอมรับ
                                                        </button>
                                                        <button type="submit" name="action" value="reject"
                                                            class="btn btn-danger btn-sm ms-2">
                                                            <i class="fas fa-times me-1"></i>ปฏิเสธ
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>



</html>