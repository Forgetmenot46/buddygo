<?php
// เรียกใช้งานไฟล์ config
require_once '../config/config.php';

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบว่าเป็นการส่งข้อมูลผ่าน POST หรือไม่
    if (
        empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['username']) ||
        empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password']) ||
        empty($_POST['birthdate']) || empty($_POST['country_id']) || empty($_POST['phone_number']) ||
        empty($_POST['languages_spoken']) || empty($_POST['interests']) || empty($_POST['gender'])
    ) {
        $error = "All fields are required.";
    } else {
        // ตรวจสอบรหัสผ่านให้ตรงกัน
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $error = "Passwords do not match.";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (!isset($error)) {
        // ดึงค่าจากฟอร์ม
        $username = $_POST['username'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $profilePicturePath = 'avatar.png'; // ระบุที่อยู่ของรูปภาพเริ่มต้น
        $birthdate = $_POST['birthdate'];
        $country_id = $_POST['country_id'];
        $phone_number = $_POST['phone_number'];
        $gender = $_POST['gender']; // เพิ่มตัวแปร gender
        $verified_status = 0; // ถ้าผู้ใช้ไม่ได้ยืนยัน
        $is_admin = 0; // ถ้าผู้ใช้ไม่ใช่แอดมิน
        $phone_verified = 0; // ถ้าหมายเลขโทรศัพท์ยังไม่ได้รับการยืนยัน
        $languages_spoken = $_POST['languages_spoken'] ?? [];
        $interests = $_POST['interests'] ?? [];

        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // ตรวจสอบว่า username ซ้ำหรือไม่
        $username_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $username_check->bind_param("s", $username);
        $username_check->execute();
        $username_check->bind_result($username_count);
        $username_check->fetch();
        $username_check->close();

        // ถ้า username ซ้ำ
        if ($username_count > 0) {
            $error = "Username already exists.";
        }

        // ตรวจสอบว่า email ซ้ำหรือไม่
        $email_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $email_check->bind_param("s", $email);
        $email_check->execute();
        $email_check->bind_result($email_count);
        $email_check->fetch();
        $email_check->close();

        // ถ้า email ซ้ำ
        if ($email_count > 0) {
            $error = "Email already exists.";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (!isset($error)) {
        // เตรียม SQL สำหรับบันทึกข้อมูลผู้ใช้
        $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, email, password, profile_picture, birthdate, country_id, phone_number, gender, verified_status, is_admin, phone_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            // ผูกค่ากับคำสั่ง SQL
            $stmt->bind_param("sssssssisssii", $username, $firstname, $lastname, $email, $hashed_password, $profilePicturePath, $birthdate, $country_id, $phone_number, $gender, $verified_status, $is_admin, $phone_verified);

            // บันทึกข้อมูล
            if ($stmt->execute()) {
                // บันทึกข้อมูลสำเร็จ
                $success = "User registered successfully!";
                $userId = $conn->insert_id;

                // บันทึกความสนใจ
                foreach ($interests as $interest_id) {
                    $stmt = $conn->prepare("INSERT INTO user_interests (user_id, interest_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $userId, $interest_id);
                    $stmt->execute();
                }

                // บันทึกภาษาที่ใช้
                foreach ($languages_spoken as $language_id) {
                    $stmt = $conn->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $userId, $language_id);
                    $stmt->execute();
                }
            } else {
                $error = "Database error: " . $stmt->error;
            }
        } else {
            $error = "Failed to prepare the SQL statement.";
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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <!-- Select2 Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />

    <style>
        /* สไตล์สำหรับพื้นหลัง */
        body {
            background-color: #F6F6F6;
            font-family: Arial, sans-serif;
            color: #333;
        }

        /* สไตล์สำหรับ container */
        .container {
            max-width: 600px;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* สไตล์สำหรับฟอร์ม */
        .form-control {
            border-radius: 8px;
            box-shadow: none;
            border: 2px solid #163172;
            /* กรอบช่องกรอกข้อมูล */
        }

        .gender,
        .interestchose {
            border-radius: 8px;
            box-shadow: none;
            border: 2px solid #163172;/
        }

        .form-control:focus {
            border-color: #4e79a7;
            box-shadow: 0 0 5px rgba(78, 121, 167, 0.6);
        }

        .alert {
            margin-top: 20px;
        }

        /* สไตล์สำหรับปุ่ม */
        .btn-primary {
            background-color: #163172;
            border-color: #163172;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: rgb(7, 71, 221);
            border-color: #163172;
        }

        /* สไตล์สำหรับส่วนหัว */
        .form-header {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;

        }

        .RegisterH2 {
            color: #163172;
        }

        .form-header h2 {
            font-size: 2rem;
            color: #333;
        }

        /* สไตล์สำหรับรูปโปรไฟล์ */
        .profile-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* สไตล์สำหรับช่องรหัสผ่าน */
        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="form-header">
            <h2 class="RegisterH2">Register</h2>
        </div>

        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success <a href='login.php' class='btn btn-link'>Go to Login</a></div>"; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- ส่วนของ Username -->
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>" required maxlength="20" placeholder="Enter your username">
            </div>

            <!-- ส่วนของชื่อและนามสกุล -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="firstname" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo isset($_POST['firstname']) ? $_POST['firstname'] : ''; ?>" required placeholder="Enter your first name">
                </div>
                <div class="col-md-6">
                    <label for="lastname" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo isset($_POST['lastname']) ? $_POST['lastname'] : ''; ?>" required placeholder="Enter your last name">
                </div>
            </div>

            <!-- ส่วนของวันเกิด -->
            <div class="mb-3">
                <label for="birthdate" class="form-label">Birthdate</label>
                <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo isset($_POST['birthdate']) ? $_POST['birthdate'] : ''; ?>" required placeholder="Select your birthdate">
            </div>

            <!-- ส่วนของเบอร์โทรศัพท์ -->
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <select class="form-control" id="country_id" name="country_id" required aria-label="Select country code">
                        <?php
                        // ดึงข้อมูลประเทศจากตาราง countriesphone
                        $countryQuery = "SELECT * FROM countriesphone";
                        $result = $conn->query($countryQuery);
                        while ($row = $result->fetch_assoc()) {
                            // เลือก country ที่เลือกไว้
                            $selected = (isset($_POST['country_id']) && $_POST['country_id'] == $row['country_id']) ? 'selected' : '';
                            echo "<option value='{$row['country_id']}' $selected>{$row['country_name']} ({$row['country_phone_id']})</option>";
                        }
                        ?>
                    </select>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo isset($_POST['phone_number']) ? $_POST['phone_number'] : ''; ?>" required placeholder="Enter phone number" aria-label="Phone number">
                </div>
            </div>

            <!-- ส่วนของอีเมล -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required placeholder="Enter your email">
            </div>

            <!-- ส่วนของเพศ -->
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-select gender" id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <!-- ส่วนของรหัสผ่าน -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>" required placeholder="Enter your password">
                </div>
            </div>

            <!-- ส่วนของยืนยันรหัสผ่าน -->
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" value="<?php echo isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''; ?>" required placeholder="Confirm your password">
                </div>
            </div>

            <div class="row">
    <!-- ส่วนของภาษา (เปลี่ยนให้เหมือน Interests) -->
    <div class="col-md-6">
        <label for="languages_spoken" class="form-label">Languages Spoken</label>
        <select class="form-select interestchose" id="multiple-select-custom-field" name="languages_spoken[]" multiple>
            <option value="1" <?php echo (isset($_POST['languages_spoken']) && in_array(1, $_POST['languages_spoken'])) ? 'selected' : ''; ?>>English</option>
            <option value="2" <?php echo (isset($_POST['languages_spoken']) && in_array(2, $_POST['languages_spoken'])) ? 'selected' : ''; ?>>Thai</option>
            <option value="3" <?php echo (isset($_POST['languages_spoken']) && in_array(3, $_POST['languages_spoken'])) ? 'selected' : ''; ?>>Chinese</option>
            <option value="4" <?php echo (isset($_POST['languages_spoken']) && in_array(4, $_POST['languages_spoken'])) ? 'selected' : ''; ?>>Japanese</option>
            <option value="5" <?php echo (isset($_POST['languages_spoken']) && in_array(5, $_POST['languages_spoken'])) ? 'selected' : ''; ?>>Spanish</option>
        </select>
    </div>

    <!-- ส่วนของความสนใจ -->
    <div class="col-md-6">
        <label for="interests" class="form-label">Interests</label>
        <select class="form-select interestchose" id="multiple-select-custom-field2" name="interests[]" multiple>
            <option value="1" <?php echo (isset($_POST['interests']) && in_array(1, $_POST['interests'])) ? 'selected' : ''; ?>>Music</option>
            <option value="2" <?php echo (isset($_POST['interests']) && in_array(2, $_POST['interests'])) ? 'selected' : ''; ?>>Fitness</option>
            <option value="3" <?php echo (isset($_POST['interests']) && in_array(3, $_POST['interests'])) ? 'selected' : ''; ?>>Photography</option>
            <option value="4" <?php echo (isset($_POST['interests']) && in_array(4, $_POST['interests'])) ? 'selected' : ''; ?>>Cooking</option>
            <option value="5" <?php echo (isset($_POST['interests']) && in_array(5, $_POST['interests'])) ? 'selected' : ''; ?>>Hiking</option>
            <option value="6" <?php echo (isset($_POST['interests']) && in_array(6, $_POST['interests'])) ? 'selected' : ''; ?>>Movies</option>
            <option value="7" <?php echo (isset($_POST['interests']) && in_array(7, $_POST['interests'])) ? 'selected' : ''; ?>>Art</option>
            <option value="8" <?php echo (isset($_POST['interests']) && in_array(8, $_POST['interests'])) ? 'selected' : ''; ?>>Travelling</option>
            <option value="9" <?php echo (isset($_POST['interests']) && in_array(9, $_POST['interests'])) ? 'selected' : ''; ?>>Gaming</option>
        </select>
    </div>
</div>


            <!-- ปุ่มสมัครสมาชิก -->
            <button type="submit" class="btn btn-primary mt-3">Sign Up</button>
        </form>

    </div>
    <br>
    <br>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // เริ่มต้นใช้งาน Select2 สำหรับฟิลด์ความสนใจ
        $(document).ready(function() {
            $('#multiple-select-custom-field').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                tags: true
            });
        });
        $(document).ready(function() {
            $('#multiple-select-custom-field2').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                tags: true
            });
        });
    </script>
</body>

</html>