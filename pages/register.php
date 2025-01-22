<?php
session_start();
require_once 'config/config.php';

if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $birthdate = $_POST['birthdate'];
    $password = $_POST['password'];
    $c_password = $_POST['c_password'];
    $country_phoneid = $_POST['country_phoneid'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $national_id = $_POST['national_id'];

    // ตรวจสอบรหัสผ่านตรงกัน
    if ($password != $c_password) {
        $_SESSION['error'] = 'รหัสผ่านไม่ตรงกัน!';
        header("location: register.php");
        return;
    }

    try {
        // ตรวจสอบ username ซ้ำ
        $check_username = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $result = $check_username->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "มีชื่อผู้ใช้นี้ในระบบแล้ว!";
            header("location: register.php");
            return;
        }

        // ตรวจสอบ email ซ้ำ
        $check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "มีอีเมลนี้ในระบบแล้ว!";
            header("location: register.php");
            return;
        }

        // เข้ารหัสรหัสผ่าน
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // เพิ่มข้อมูลผู้ใช้
        $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, birthdate, password, country_phoneid, phone_number, email, gender, national_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $username, $firstname, $lastname, $birthdate, $passwordHash, $country_phoneid, $phone_number, $email, $gender, $national_id);
        $stmt->execute();

        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
        header("location: login.php");
    } catch (Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("location: register.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }
        .register-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (isset($_SESSION['error'])) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <div class="card register-card">
                    <div class="card-header text-center py-3">
                        <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>สมัครสมาชิก</h3>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ชื่อผู้ใช้</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ชื่อ</label>
                                    <input type="text" class="form-control" name="firstname" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">นามสกุล</label>
                                    <input type="text" class="form-control" name="lastname" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">วันเกิด</label>
                                    <input type="date" class="form-control" name="birthdate" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เพศ</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="ชาย">ชาย</option>
                                        <option value="หญิง">หญิง</option>
                                        <option value="อื่นๆ">อื่นๆ</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">รหัสประเทศ</label>
                                    <input type="text" class="form-control" name="country_phoneid" placeholder="+66" required>
                                </div>
                                <div class="col-md-9 mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="tel" class="form-control" name="phone_number" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">เลขบัตรประชาชน</label>
                                <input type="text" class="form-control" name="national_id" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">รหัสผ่าน</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ยืนยันรหัสผ่าน</label>
                                    <input type="password" class="form-control" name="c_password" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="signup" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
