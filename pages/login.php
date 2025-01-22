<?php
session_start();
require_once '../config/config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND account_status = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user['PASSWORD'])) {
            // Login สำเร็จ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ!";
            
            header("location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $_SESSION['error'] = "ไม่พบบัญชีผู้ใช้ในระบบ หรือบัญชีถูกระงับ!";
    }
    $stmt->close();
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Title</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            background: #f8f9fa;
            font-family: 'Kanit', sans-serif;
        }

        /* Navbar Styles */
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }

        /* Login Card Styles */
        .login-card {
            transition: transform 0.3s ease;
            border: none;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .card-header {
            border-bottom: none;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        }

        /* Form Styles */
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
        }

        /* Button Styles */
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

        /* Footer Styles */
        footer {
            background: linear-gradient(135deg, #212529, #343a40);
            margin-top: auto;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.8s ease-out;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .navbar-brand img {
                height: 30px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <!-- Alert Messages -->
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

                    <div class="card shadow-lg border-0 rounded-lg mt-5 login-card">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h3 class="mb-0"><i class="fas fa-user-circle me-2"></i>เข้าสู่ระบบ</h3>
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">รหัสผ่าน</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">จดจำฉัน</label>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="login" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center py-3">
                            <div class="small"><a href="register.php">ยังไม่มีบัญชี? สมัครสมาชิก</a></div>
                            <div class="small mt-2"><a href="forgot-password.php">ลืมรหัสผ่าน?</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JavaScript Libraries -->
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"></script>

    <style>
    .social-icon {
        transition: all 0.3s ease;
        padding: 8px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        width: 40px;
        height: 40px;
    }

    .social-icon:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.2);
    }

    .social-icon i {
        transition: all 0.3s ease;
    }

    .social-icon:hover i.fa-facebook {
        color: #1877f2;
    }

    .social-icon:hover i.fa-twitter {
        color: #1da1f2;
    }

    .social-icon:hover i.fa-instagram {
        color: #e4405f;
    }

    .social-icon:hover i.fa-linkedin {
        color: #0077b5;
    }
    </style>
</body>

</html>