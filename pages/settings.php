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

// ดึงข้อมูลผู้ใช้
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// จัดการการเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'
                ];
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'รหัสผ่านใหม่ไม่ตรงกัน'
            ];
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า - BuddyGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-card {
            max-width: 600px;
            margin: 0 auto;
        }

        .version-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .copyright {
            text-align: center;
            padding: 20px 0;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>

    <div class="row">
        <div class="col-4"><?php include '../includes/header.php'; ?></div>
    </div>
    <div class="container mt-4">
        <div class="settings-card">
            <h2 class="mb-4">
                <i class="fas fa-cog me-2 text-primary"></i>ตั้งค่า
            </h2>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['alert']['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">ข้อมูลผู้ใช้</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted">อีเมล</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">ชื่อผู้ใช้</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">เปลี่ยนรหัสผ่าน</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                        </button>
                    </form>
                </div>
            </div>

           
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>