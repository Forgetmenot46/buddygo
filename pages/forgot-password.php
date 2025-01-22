<?php
session_start();
require_once '../config/config.php';

if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    
    // เช็คว่ามีอีเมลนี้ในระบบหรือไม่
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // สร้าง token สำหรับรีเซ็ตรหัสผ่าน
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // บันทึก token ลงในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();
        
        $_SESSION['success'] = "กรุณาตรวจสอบอีเมลของคุณเพื่อรีเซ็ตรหัสผ่าน";
    } else {
        $_SESSION['error'] = "ไม่พบอีเมลนี้ในระบบ";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f8f9fa;
        }
        .forgot-card {
            max-width: 500px;
            margin: 2rem auto;
            animation: fadeIn 0.8s ease-out;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border: none;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (isset($_SESSION['error'])) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php if (isset($_SESSION['success'])) { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <div class="card forgot-card">
                    <div class="card-header text-center py-3">
                        <h3 class="mb-0"><i class="fas fa-key me-2"></i>ลืมรหัสผ่าน</h3>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-center mb-4">กรุณากรอกอีเมลที่ใช้ในการลงทะเบียน เราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านไปให้คุณ</p>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="reset_password" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>ส่งลิงก์รีเซ็ตรหัสผ่าน
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>กลับไปหน้าเข้าสู่ระบบ
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
