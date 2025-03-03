<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// อัพเดทสถานะการอ่านแจ้งเตือน
if (isset($_POST['mark_as_read'])) {
    $notification_id = $_POST['notification_id'];
    $update_sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $notification_id, $user_id);
    $update_stmt->execute();
    exit();
}

// ดึงการแจ้งเตือนทั้งหมดของผู้ใช้
$notifications_sql = "SELECT n.*, 
                            u.username as from_username,
                            u.profile_picture as from_profile_picture,
                            CASE 
                                WHEN n.type = 'user_report' THEN 'การรายงานผู้ใช้'
                                WHEN n.type = 'report_status' THEN 'สถานะการรายงาน'
                                ELSE 'การแจ้งเตือน'
                            END as type_text
                     FROM notifications n
                     LEFT JOIN users u ON n.from_user_id = u.id
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
    <link href="../assets/css/profilestyle.css" rel="stylesheet">
    <style>
        .notification-item {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            border-left-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .notification-type {
            font-size: 0.85rem;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: #e9ecef;
        }
        .notification-type.user-report {
            background-color: #dc3545;
            color: white;
        }
        .notification-type.report-status {
            background-color: #198754;
            color: white;
        }
    </style>
</head>
<body>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-12 col-md-8 mt-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">การแจ้งเตือน</h4>
                    <?php if ($notifications->num_rows > 0): ?>
                        <button class="btn btn-outline-primary btn-sm" id="markAllRead">
                            <i class="fas fa-check-double me-1"></i>ทำเครื่องหมายว่าอ่านทั้งหมด
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <div class="notification-item p-3 mb-2 <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                                 data-notification-id="<?php echo $notification['id']; ?>">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo getProfileImage($notification['from_user_id']); ?>"
                                         class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="notification-type <?php echo strtolower(str_replace('_', '-', $notification['type'])); ?>">
                                                <?php echo $notification['type_text']; ?>
                                            </span>
                                            <small class="notification-time">
                                                <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="btn btn-link btn-sm p-0 mt-2 mark-as-read">
                                                ทำเครื่องหมายว่าอ่านแล้ว
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">ไม่มีการแจ้งเตือนในขณะนี้</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ฟังก์ชันสำหรับทำเครื่องหมายว่าอ่านแล้ว
            function markAsRead(notificationId) {
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `mark_as_read=1&notification_id=${notificationId}`
                })
                .then(response => {
                    if (response.ok) {
                        const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        notification.classList.remove('unread');
                        const markAsReadBtn = notification.querySelector('.mark-as-read');
                        if (markAsReadBtn) {
                            markAsReadBtn.remove();
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Event listener สำหรับปุ่มทำเครื่องหมายว่าอ่านแล้ว
            document.querySelectorAll('.mark-as-read').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationId = this.closest('.notification-item').dataset.notificationId;
                    markAsRead(notificationId);
                });
            });

            // Event listener สำหรับปุ่มทำเครื่องหมายว่าอ่านทั้งหมด
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        markAsRead(item.dataset.notificationId);
                    });
                });
            }
        });
    </script>
</body>
</html>
