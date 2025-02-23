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
    <style>
    :root {
        --primary-color: #4A90E2;
        --unread-bg: #EBF5FF;
        --border-radius: 15px;
        --shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .notifications-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .notification-card {
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        background-color: #fff;
        transition: all 0.3s ease;
    }

    .notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    .notification-card.unread {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
    }

    .notification-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        background: var(--primary-color);
        color: white;
    }

    .notification-time {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .notification-title {
        font-weight: 600;
        color: #2C3E50;
        margin-bottom: 0.25rem;
    }

    .notification-message {
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn-action {
        padding: 0.375rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-accept {
        background-color: #2ecc71;
        color: white;
        border: none;
    }

    .btn-accept:hover {
        background-color: #27ae60;
        transform: translateY(-1px);
    }

    .btn-reject {
        background-color: #e74c3c;
        color: white;
        border: none;
    }

    .btn-reject:hover {
        background-color: #c0392b;
        transform: translateY(-1px);
    }

    .btn-view {
        background-color: var(--primary-color);
        color: white;
        border: none;
    }

    .btn-view:hover {
        background-color: #357ABD;
        transform: translateY(-1px);
    }

    .empty-notifications {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .empty-icon {
        font-size: 3rem;
        color: #cbd5e0;
        margin-bottom: 1rem;
    }

    .qr-code-container {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        text-align: center;
    }

    .qr-code-container img {
        max-width: 200px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .notification-actions {
            flex-direction: column;
        }
        
        .btn-action {
            width: 100%;
        }
    }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
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

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 