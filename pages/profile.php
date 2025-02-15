<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT u.*, 
           GROUP_CONCAT(DISTINCT i.interest_name) as interest_list,
           GROUP_CONCAT(DISTINCT l.language_name) as language_list
    FROM users u
    LEFT JOIN user_interests ui ON u.id = ui.user_id
    LEFT JOIN interests i ON ui.interest_id = i.id
    LEFT JOIN user_languages ul ON u.id = ul.user_id
    LEFT JOIN languages l ON ul.language_id = l.id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['new_profile_picture'])) {
    // ลบรูปเก่าถ้ามี
    $oldProfilePicture = $user['profile_picture'];
    if ($oldProfilePicture && $oldProfilePicture !== 'default.png') {
        unlink("../uploads/profile_pictures/" . $oldProfilePicture);
    }

    // อัปโหลดรูปใหม่
    $uploadDir = "../uploads/profile_pictures/";
    $fileExtension = pathinfo($_FILES['new_profile_picture']['name'], PATHINFO_EXTENSION);
    $newFileName = date('YmdHis') . '_' . htmlspecialchars($user['username']) . '.' . $fileExtension; // เปลี่ยนชื่อไฟล์
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['new_profile_picture']['tmp_name'], $targetPath)) {
        // อัปเดตชื่อไฟล์ในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newFileName, $user_id);
        $stmt->execute();
    } else {
        $error = "Failed to upload the new image.";
    }
}

if (isset($_GET['update']) && $_GET['update'] == 'success') {
    echo '<script>
            alert("ข้อมูลโปรไฟล์ของคุณถูกเปลี่ยนแปลงเรียบร้อยแล้ว!");
            window.location.href = "profile.php"; // เปลี่ยนเส้นทางไปยังหน้าโปรไฟล์
          </script>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card profile-card">
                        <div class="card-body text-center">
                            <img src="../uploads/profile_pictures/<?php echo $user['profile_picture'] ?: 'default.png'; ?>"
                                class="rounded-circle profile-image mb-3" alt="Profile Picture">
                            <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                            <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                Edit Profile
                            </button>
                            <form action="logout.php" method="POST" class="d-inline" onsubmit="return confirmLogout();">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 mb-3">
                    <div class="card d-flex flex-column" style="height: 100%;"> <!-- เพิ่ม d-flex และ flex-column -->
                        <div class="card-body flex-grow-1">
                            <h4>Personal Information</h4>
                            <div class="row">
                                <div class="col-md-6 mt-2">
                                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
                                    <p><strong>Languages:</strong></p>
                                    <div class="badge-container">
                                        <?php
                                        if ($user['language_list'] !== 'Not specified') {
                                            foreach (explode(',', $user['language_list']) as $language) {
                                                echo '<span class="badge me-2 mb-2" style="background-color: var(--primary-color);">' . htmlspecialchars(trim($language)) . '</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">Not specified</span>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-2">
                                    <p><strong>Interests:</strong></p>
                                    <div class="badge-container">
                                        <?php
                                        if ($user['interest_list'] !== 'Not specified') {
                                            foreach (explode(',', $user['interest_list']) as $interest) {
                                                echo '<span class="badge me-2 mb-2" style="background-color: var(--primary-color);">' . htmlspecialchars(trim($interest)) . '</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">Not specified</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="new_profile_picture" name="new_profile_picture">
                        </div>

                        <button type="submit" class="btn btn-primary" onclick="return confirmChange();">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            return confirm("คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?"); // Show confirmation alert
        }

        function confirmChange() {
            return confirm("คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนชื่อและนามสกุล?"); // Show confirmation alert
        }
    </script>
</body>

</html>