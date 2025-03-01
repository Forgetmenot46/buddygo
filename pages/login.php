<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit();
        }
    }
    $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        /* ขยายหน้าจอให้เต็ม */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #F6F6F6;
            color: #163172; /* เปลี่ยนสีข้อความทั้งหมด */
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* รูปภาพฝั่งซ้าย */
        .login-image {
            width: 50%;
            height: 100vh;
            object-fit: cover;
            border-radius: 12px 0 0 12px;
        }

        /* ฟอร์ม Login ฝั่งขวา */
        .login-container {
            width: 50%;
            padding: 30px;
            background: #F6F6F6;
            border-radius: 0 12px 12px 0;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 15px;
            font-size: 16px;
        }

        .form-control {
            border-radius: 8px;
            box-shadow: none;
            border: 2px solid #163172; /* กรอบช่องกรอกข้อมูล */
        }

        .form-control:focus {
            border-color: #0056b3; /* กรอบสีฟ้าเมื่อช่องกรอกข้อมูลโฟกัส */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* เพิ่มเงา */
        }

        .btn-login {
            background-color: #163172; /* ปุ่มเป็นสี #163172 */
            color: #F6F6F6; /* ตัวอักษรในปุ่มเป็นสี #F6F6F6 */
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
        }

        .btn-login:hover {
            background-color: #0056b3; /* ปุ่มจะเปลี่ยนสีเมื่อ hover */
        }

        .text-center p {
            font-size: 16px;
        }

        .text-center a {
            font-size: 16px;
            color: #163172; /* เปลี่ยนสีลิงก์ */
        }

        .text-center a:hover {
            text-decoration: underline;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 10px; /* แก้ไขจาก 50% เป็น 10px */
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- ใส่รูปภาพฝั่งซ้าย -->
        <img src="../assets/images/3-men-walk-along-hill-with-d.jpg" alt="Login Image" class="login-image">

        <!-- ฟอร์ม Login ข้างขวา -->
        <div class="login-container">
            <h2>เข้าสู่ระบบ</h2>
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST" action="" class="mt-4">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="password" name="password" required>
                        
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-login">เข้าสู่ระบบ</button>
                </div>

                <div class="mt-3 text-center">
                    <p>ยังไม่มีบัญชีหรอ ? <a href="../pages/register.php" class="btn btn-link">สมัครสมาชิก</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    

</body>

</html>
