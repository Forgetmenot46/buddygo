<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบความเรียบร้อยของฟอร์ม
    if (empty($_POST['username']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password']) || empty($_POST['birthdate']) || empty($_POST['national_id']) || empty($_POST['country_phoneid']) || empty($_POST['phone_number']) || empty($_POST['languages_spoken']) || empty($_POST['interests'])) {
        $error = "All fields are required.";
    }

    // ถ้าทุกอย่างกรอกครบแล้ว จะทำการตรวจสอบ username และ email ซ้ำ
    if (!isset($error)) {
        $username = trim($_POST['username']);
        // ตรวจสอบว่า username ซ้ำหรือไม่
        $checkUsernameQuery = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $conn->prepare($checkUsernameQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($usernameExists);
        $stmt->fetch();
        $stmt->close();

        if ($usernameExists > 0) {
            // หาก username ซ้ำให้แจ้งเตือนผู้ใช้
            $error = "Username already taken. Please choose a different one.";
        } else {
            $email = trim($_POST['email']);
            // ตรวจสอบว่า email ซ้ำหรือไม่
            $checkEmailQuery = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $conn->prepare($checkEmailQuery);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($emailExists);
            $stmt->fetch();
            $stmt->close();

            if ($emailExists > 0) {
                // หาก email ซ้ำให้แจ้งเตือนผู้ใช้
                $error = "Email already taken. Please use a different email address.";
            } else {
                // กรณีที่ username และ email ยังไม่เคยใช้
                $firstname = trim($_POST['firstname']);
                $lastname = trim($_POST['lastname']);
                $password = trim($_POST['password']);
                $confirm_password = trim($_POST['confirm_password']);
                $birthdate = $_POST['birthdate'];
                $national_id = $_POST['national_id'];
                $country_phoneid = $_POST['country_phoneid'];
                $phone_number = $_POST['phone_number'];
                $languages_spoken = isset($_POST['languages_spoken']) ? implode(',', $_POST['languages_spoken']) : '';
                $interests = isset($_POST['interests']) ? implode(',', $_POST['interests']) : '';

                // ตรวจสอบรหัสผ่านว่าไม่ตรงกันหรือไม่
                if ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // ขั้นตอนการอัพโหลดไฟล์โปรไฟล์
                    $profilePicturePath = null;
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
                        $uploadDir = "../uploads/profile_pictures/";
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $newFileName = uniqid("profile_", true) . '.' . $fileExtension;
                        $targetPath = $uploadDir . $newFileName;

                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                            $profilePicturePath = $newFileName;
                        } else {
                            $error = "Failed to upload the image.";
                        }
                    }

                    // ถ้าไม่มี error ก็จะทำการบันทึกข้อมูล
                    if (!isset($error)) {
                        $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, email, password, profile_picture, birthdate, national_id, country_phoneid, phone_number, languages_spoken, interests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("ssssssssssss", $username, $firstname, $lastname, $email, $hashed_password, $profilePicturePath, $birthdate, $national_id, $country_phoneid, $phone_number, $languages_spoken, $interests);

                            if ($stmt->execute()) {
                                $success = "User registered successfully!";
                            } else {
                                $error = "Database error: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error = "Failed to prepare the SQL statement.";
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #profilePreview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center">Register</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

        <form method="POST" action="" class="mt-4" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="profile_picture">Upload Profile Picture:</label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" onchange="previewImage(event)">
                <div class="mt-3">
                    <img id="profilePreview" src="" alt="Profile Preview">
                </div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required maxlength="20">
            </div>

            <div class="mb-3">
                <label for="firstname" class="form-label">First Name</label>
                <input type="text" class="form-control" id="firstname" name="firstname" required>
            </div>

            <div class="mb-3">
                <label for="lastname" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lastname" name="lastname" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="mb-3">
                <label for="birthdate" class="form-label">Birthdate</label>
                <input type="date" class="form-control" id="birthdate" name="birthdate" required>
            </div>

            <div class="mb-3">
                <label for="national_id" class="form-label">Nationality</label>
                <select class="form-control" id="national_id" name="national_id" required>
                    <option value="Thai">Thai</option>
                    <option value="American">American</option>
                    <option value="British">British</option>
                    <option value="Japanese">Japanese</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Indian">Indian</option>
                    <option value="Australian">Australian</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="country_phoneid" class="form-label">Country Number ID</label>
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-control" id="country_phoneid" name="country_phoneid" required>
                            <option value="+66">Thailand (+66)</option>
                            <option value="+1">United States (+1)</option>
                            <option value="+44">United Kingdom (+44)</option>
                            <option value="+81">Japan (+81)</option>
                            <option value="+86">China (+86)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="languages_spoken" class="form-label">Languages Spoken</label>
                <select class="form-control" id="languages_spoken" name="languages_spoken[]" multiple required>
                    <option value="english">English</option>
                    <option value="thai">Thai</option>
                    <option value="chinese">Chinese</option>
                    <option value="japanese">Japanese</option>
                    <option value="spanish">Spanish</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="interests" class="form-label">Interests</label>
                <select class="form-control" id="interests" name="interests[]" multiple required>
                    <option value="sports">Sports</option>
                    <option value="board_games">Board Games</option>
                    <option value="traveling">Traveling</option>
                    <option value="music">Music</option>
                    <option value="reading">Reading</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('profilePreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>
