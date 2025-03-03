<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// จัดการการกระทำของแอดมิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $report_id = $_POST['report_id'];
        $user_id = $_POST['user_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // อัพเดทสถานะรายงาน
        $update_report_sql = "UPDATE user_reports SET status = ?, admin_notes = ? WHERE report_id = ?";
        $update_report_stmt = $conn->prepare($update_report_sql);
        
        // ดำเนินการตามการกระทำ
        switch ($action) {
            case 'suspend':
                $duration = $_POST['duration']; // จำนวนวันที่จะพักบัญชี
                $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));
                
                // บันทึกการพักบัญชี
                $suspend_sql = "INSERT INTO user_suspensions (user_id, start_date, end_date, reason) 
                              VALUES (?, NOW(), ?, ?)";
                $suspend_stmt = $conn->prepare($suspend_sql);
                $suspend_stmt->bind_param("iss", $user_id, $end_date, $admin_notes);
                $suspend_stmt->execute();
                
                // อัพเดทสถานะรายงาน
                $status = 'resolved';
                $update_report_stmt->bind_param("ssi", $status, $admin_notes, $report_id);
                $update_report_stmt->execute();
                
                // แจ้งเตือนผู้ใช้
                $notification_msg = "บัญชีของคุณถูกพักการใช้งานเป็นเวลา $duration วัน เนื่องจาก: $admin_notes";
                break;
                
            case 'ban':
                // บันทึกการแบนบัญชี
                $ban_sql = "INSERT INTO user_bans (user_id, ban_date, reason) VALUES (?, NOW(), ?)";
                $ban_stmt = $conn->prepare($ban_sql);
                $ban_stmt->bind_param("is", $user_id, $admin_notes);
                $ban_stmt->execute();
                
                // อัพเดทสถานะผู้ใช้
                $update_user_sql = "UPDATE users SET status = 'banned' WHERE id = ?";
                $update_user_stmt = $conn->prepare($update_user_sql);
                $update_user_stmt->bind_param("i", $user_id);
                $update_user_stmt->execute();
                
                // อัพเดทสถานะรายงาน
                $status = 'resolved';
                $update_report_stmt->bind_param("ssi", $status, $admin_notes, $report_id);
                $update_report_stmt->execute();
                
                // แจ้งเตือนผู้ใช้
                $notification_msg = "บัญชีของคุณถูกระงับการใช้งานถาวร เนื่องจาก: $admin_notes";
                break;
                
            case 'dismiss':
                $status = 'dismissed';
                $update_report_stmt->bind_param("ssi", $status, $admin_notes, $report_id);
                $update_report_stmt->execute();
                
                // แจ้งเตือนผู้ใช้
                $notification_msg = "รายงานของคุณถูกยกเลิก เนื่องจาก: $admin_notes";
                break;
        }
        
        // สร้างการแจ้งเตือนสำหรับผู้ใช้
        $notification_sql = "INSERT INTO notifications (user_id, from_user_id, type, message) 
                           VALUES (?, ?, 'report_status', ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_stmt->bind_param("iis", $user_id, $_SESSION['user_id'], $notification_msg);
        $notification_stmt->execute();
        
        header("Location: manage_reports.php");
        exit();
    }
}

// ดึงรายการรายงานทั้งหมด
$reports_sql = "SELECT r.*, 
                u1.username as reported_username,
                u2.username as reporter_username,
                u1.id as reported_user_id,
                (SELECT COUNT(*) FROM user_reports WHERE reported_user_id = u1.id) as total_reports,
                (SELECT COUNT(*) FROM user_reports WHERE reported_user_id = u1.id AND status = 'resolved') as resolved_reports
                FROM user_reports r
                JOIN users u1 ON r.reported_user_id = u1.id
                JOIN users u2 ON r.reporting_user_id = u2.id
                ORDER BY r.created_at DESC";
$reports = $conn->query($reports_sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายงานผู้ใช้ - BuddyGo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .report-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .report-card.pending { border-left-color: #ffc107; }
        .report-card.resolved { border-left-color: #198754; }
        .report-card.dismissed { border-left-color: #6c757d; }
        .report-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">จัดการรายงานผู้ใช้</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($reports->num_rows > 0): ?>
                            <?php while ($report = $reports->fetch_assoc()): ?>
                                <div class="report-card card <?php echo $report['status']; ?> p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">
                                                ผู้ถูกรายงาน: <?php echo htmlspecialchars($report['reported_username']); ?>
                                                <span class="badge bg-warning report-badge ms-2">
                                                    รายงานทั้งหมด: <?php echo $report['total_reports']; ?>
                                                </span>
                                                <span class="badge bg-danger report-badge">
                                                    ถูกลงโทษ: <?php echo $report['resolved_reports']; ?>
                                                </span>
                                            </h5>
                                            <p class="mb-1">
                                                <small class="text-muted">
                                                    รายงานโดย: <?php echo htmlspecialchars($report['reporter_username']); ?>
                                                    | วันที่: <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?>
                                                </small>
                                            </p>
                                            <p class="mb-2">
                                                <span class="badge bg-primary">
                                                    <?php 
                                                    switch($report['violation_type']) {
                                                        case 'no_show': echo 'ไม่มาตามนัด'; break;
                                                        case 'harassment': echo 'การคุกคาม/ก่อกวน'; break;
                                                        case 'inappropriate': echo 'พฤติกรรมไม่เหมาะสม'; break;
                                                        case 'scam': echo 'การหลอกลวง'; break;
                                                        default: echo 'อื่นๆ';
                                                    }
                                                    ?>
                                                </span>
                                            </p>
                                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                            <?php if ($report['admin_notes']): ?>
                                                <div class="alert alert-info">
                                                    <strong>บันทึกของแอดมิน:</strong> <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($report['status'] === 'pending'): ?>
                                            <div class="ms-3">
                                                <button class="btn btn-warning btn-sm mb-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#suspendModal"
                                                        data-report-id="<?php echo $report['report_id']; ?>"
                                                        data-user-id="<?php echo $report['reported_user_id']; ?>">
                                                    <i class="fas fa-pause me-1"></i>พักการใช้งาน
                                                </button>
                                                <button class="btn btn-danger btn-sm mb-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#banModal"
                                                        data-report-id="<?php echo $report['report_id']; ?>"
                                                        data-user-id="<?php echo $report['reported_user_id']; ?>">
                                                    <i class="fas fa-ban me-1"></i>แบนถาวร
                                                </button>
                                                <button class="btn btn-secondary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#dismissModal"
                                                        data-report-id="<?php echo $report['report_id']; ?>"
                                                        data-user-id="<?php echo $report['reported_user_id']; ?>">
                                                    <i class="fas fa-times me-1"></i>ยกเลิกรายงาน
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $report['status'] === 'resolved' ? 'success' : 'secondary'; ?>">
                                                <?php echo $report['status'] === 'resolved' ? 'ดำเนินการแล้ว' : 'ยกเลิก'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="text-muted">ไม่มีรายงานที่ต้องดำเนินการ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal พักการใช้งาน -->
    <div class="modal fade" id="suspendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">พักการใช้งานบัญชี</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_reports.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="suspend">
                        <input type="hidden" name="report_id" id="suspendReportId">
                        <input type="hidden" name="user_id" id="suspendUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">ระยะเวลาพักการใช้งาน (วัน)</label>
                            <select class="form-select" name="duration" required>
                                <option value="3">3 วัน</option>
                                <option value="7">7 วัน</option>
                                <option value="15">15 วัน</option>
                                <option value="30">30 วัน</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">เหตุผล</label>
                            <textarea class="form-control" name="admin_notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning">พักการใช้งาน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แบนถาวร -->
    <div class="modal fade" id="banModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แบนบัญชีถาวร</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_reports.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ban">
                        <input type="hidden" name="report_id" id="banReportId">
                        <input type="hidden" name="user_id" id="banUserId">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            การแบนบัญชีเป็นการดำเนินการถาวร ไม่สามารถยกเลิกได้
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">เหตุผล</label>
                            <textarea class="form-control" name="admin_notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-danger">แบนบัญชี</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal ยกเลิกรายงาน -->
    <div class="modal fade" id="dismissModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยกเลิกรายงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_reports.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="dismiss">
                        <input type="hidden" name="report_id" id="dismissReportId">
                        <input type="hidden" name="user_id" id="dismissUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">เหตุผลในการยกเลิก</label>
                            <textarea class="form-control" name="admin_notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">ยืนยัน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // จัดการ Modal สำหรับพักการใช้งาน
            const suspendModal = document.getElementById('suspendModal');
            suspendModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const reportId = button.getAttribute('data-report-id');
                const userId = button.getAttribute('data-user-id');
                document.getElementById('suspendReportId').value = reportId;
                document.getElementById('suspendUserId').value = userId;
            });

            // จัดการ Modal สำหรับแบน
            const banModal = document.getElementById('banModal');
            banModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const reportId = button.getAttribute('data-report-id');
                const userId = button.getAttribute('data-user-id');
                document.getElementById('banReportId').value = reportId;
                document.getElementById('banUserId').value = userId;
            });

            // จัดการ Modal สำหรับยกเลิก
            const dismissModal = document.getElementById('dismissModal');
            dismissModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const reportId = button.getAttribute('data-report-id');
                const userId = button.getAttribute('data-user-id');
                document.getElementById('dismissReportId').value = reportId;
                document.getElementById('dismissUserId').value = userId;
            });
        });
    </script>
</body>
</html> 