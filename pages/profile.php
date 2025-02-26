<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

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

// ดึงโพสต์ของผู้ใช้
$sql = "SELECT p.*, u.username, 
        COUNT(DISTINCT pm.user_id) as current_members,
        GROUP_CONCAT(DISTINCT i.interest_name) as interests
        FROM community_posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN post_members pm ON p.post_id = pm.post_id AND pm.status = 'joined'
        LEFT JOIN post_interests pi ON p.post_id = pi.post_id
        LEFT JOIN interests i ON pi.interest_id = i.id
        WHERE p.user_id = ? 
        GROUP BY p.post_id
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="../assets/css/profilestyle.css">
</head>

<body>

    <div class="row ">
        <!-- คอลัมน์ซ้าย (Sidebar) -->
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <div class="container mt-4">


            <div class="main-content">
                <div class="container">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="profile-card">
                                <div class="card-body text-center">
                                    <img src="<?php
                                                $profile_pic = $user['profile_picture'];
                                                if (strpos($profile_pic, 'default') === 0) {
                                                    echo "../assets/images/default_profiles/" . $profile_pic;
                                                } else {
                                                    echo "../uploads/profile_pictures/" . ($profile_pic ?: 'default1.png');
                                                }
                                                ?>" class="profile-image rounded-circle" alt="Profile Picture">
                                    <h3 class="profile-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    <div class="notifications-badge mb-3">
                                        <?php
                                        $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                                        $unread_stmt = $conn->prepare($unread_sql);
                                        $unread_stmt->bind_param("i", $user_id);
                                        $unread_stmt->execute();
                                        $unread_count = $unread_stmt->get_result()->fetch_assoc()['count'];
                                        if ($unread_count > 0):
                                        ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-center align-items-center flex-wrap pb-3">
                                        <button class="btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editProfileModal" data-tooltip="แก้ไขข้อมูลส่วนตัวของคุณ">
                                            <i class="fas fa-edit me-2"></i>แก้ไขโปรไฟล์
                                        </button>
                                        <form action="logout.php" method="POST" class="d-inline" onsubmit="return confirmLogout();">
                                            <button type="submit" class="btn-logout" data-tooltip="ออกจากระบบ">
                                                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 mb-3">
                            <div class="info-card">
                                <div class="info-section">
                                    <h4 class="info-title">
                                        <i class="fas fa-user-circle text-primary"></i>
                                        ข้อมูลส่วนตัว
                                    </h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">ความสนใจ</div>
                                            <div class="badge-container">
                                                <?php
                                                if ($user['interest_list'] !== 'Not specified') {
                                                    foreach (explode(',', $user['interest_list']) as $interest) {
                                                        echo '<span class="custom-badge">' . htmlspecialchars(trim($interest)) . '</span>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">ภาษา</div>
                                            <div class="badge-container">
                                                <?php
                                                if ($user['language_list'] !== 'Not specified') {
                                                    foreach (explode(',', $user['language_list']) as $language) {
                                                        echo '<span class="custom-badge">' . htmlspecialchars(trim($language)) . '</span>';
                                                    }
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
            <div class="modal fade" id="editProfileModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">แก้ไขข้อมูลส่วนตัว</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">เลือกรูปโปรไฟล์</label>
                                    <div class="d-flex gap-3 mb-3">
                                        <!-- รูปเริ่มต้นที่ 1 -->
                                        <div class="avatar-select">
                                            <input type="radio" name="selected_avatar" value="1" id="avatar1" class="d-none">
                                            <label for="avatar1">
                                                <img src="../assets/images/default_profiles/default1.png"
                                                    class="rounded-circle avatar-img"
                                                    width="80" height="80"
                                                    alt="Avatar 1">
                                            </label>
                                        </div>

                                        <!-- รูปเริ่มต้นที่ 2 -->
                                        <div class="avatar-select">
                                            <input type="radio" name="selected_avatar" value="2" id="avatar2" class="d-none">
                                            <label for="avatar2">
                                                <img src="../assets/images/default_profiles/default2.png"
                                                    class="rounded-circle avatar-img"
                                                    width="80" height="80"
                                                    alt="Avatar 2">
                                            </label>
                                        </div>

                                        <!-- รูปเริ่มต้นที่ 3 -->
                                        <div class="avatar-select">
                                            <input type="radio" name="selected_avatar" value="3" id="avatar3" class="d-none">
                                            <label for="avatar3">
                                                <img src="../assets/images/default_profiles/default3.png"
                                                    class="rounded-circle avatar-img"
                                                    width="80" height="80"
                                                    alt="Avatar 3">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">หรืออัพโหลดรูปของคุณ</label>
                                    <input type="file" class="form-control" name="new_profile_picture" accept="image/*"
                                        onchange="document.querySelectorAll('input[name=selected_avatar]').forEach(r => r.checked = false);">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ชื่อ</label>
                                    <input type="text" class="form-control" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">นามสกุล</label>
                                    <input type="text" class="form-control" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">ความสนใจ</label>
                                    <div class="interest-tags-container">
                                        <?php
                                        // ดึงข้อมูล interests ทั้งหมด
                                        $all_interests_sql = "SELECT * FROM interests ORDER BY interest_name";
                                        $all_interests = $conn->query($all_interests_sql);

                                        // ดึงข้อมูล interests ของผู้ใช้
                                        $user_interests_sql = "SELECT interest_id FROM user_interests WHERE user_id = ?";
                                        $user_interests_stmt = $conn->prepare($user_interests_sql);
                                        $user_interests_stmt->bind_param("i", $user_id);
                                        $user_interests_stmt->execute();
                                        $result = $user_interests_stmt->get_result();

                                        $user_interests = [];
                                        while ($row = $result->fetch_assoc()) {
                                            $user_interests[] = $row['interest_id'];
                                        }

                                        while ($interest = $all_interests->fetch_assoc()):
                                            $is_selected = in_array($interest['id'], $user_interests);
                                        ?>
                                            <label class="interest-tag <?php echo $is_selected ? 'active' : ''; ?>">
                                                <input type="checkbox"
                                                    name="interests[]"
                                                    value="<?php echo $interest['id']; ?>"
                                                    <?php echo $is_selected ? 'checked' : ''; ?>
                                                    style="display: none;">
                                                <?php echo htmlspecialchars($interest['interest_name']); ?>
                                            </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- อัปเดตข้อมูล -->
            <script>
                <?php if (isset($_SESSION['alert'])): ?>
                    alert('<?php echo addslashes($_SESSION['alert']['message']); ?>');
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>
            </script>

            <!-- เพิ่ม JavaScript -->
            <script>
                $(document).ready(function() {
                    // จัดการการคลิกที่ interest tag
                    $('.interest-tag').on('click', function(e) {
                        e.preventDefault();
                        const ripple = document.createElement('div');
                        ripple.classList.add('ripple');
                        this.appendChild(ripple);

                        setTimeout(() => {
                            ripple.remove();
                        }, 1000);

                        $(this).toggleClass('active');
                        const checkbox = $(this).find('input[type="checkbox"]');
                        checkbox.prop('checked', !checkbox.prop('checked'));
                    });
                });

                document.addEventListener('DOMContentLoaded', function() {
                    // เมื่อเลือกรูปเริ่มต้น ใ้ล้างค่าในช่องอัพโหลดไฟล์
                    document.querySelectorAll('input[name="selected_avatar"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            if (this.checked) {
                                document.querySelector('input[name="new_profile_picture"]').value = '';
                            }
                        });
                    });

                    // เมื่อเลือกไฟล์อัพโหลด ให้ยกเลิกการเลือกรูปเริ่มต้น
                    document.querySelector('input[name="new_profile_picture"]').addEventListener('change', function() {
                        if (this.value) {
                            document.querySelectorAll('input[name="selected_avatar"]').forEach(radio => {
                                radio.checked = false;
                            });
                        }
                    });
                });

                function confirmLogout() {
                    if (formChanged) {
                        return confirm("คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากระบบหรือไม่?");
                    }
                    return confirm("คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?");
                }
            </script>

            <?php include '../includes/footer.php'; ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function confirmChange() {
                    return confirm("คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนชื่อและนามสกุล?"); // Show confirmation alert
                }
            </script>
        </div>


    </div>

</body>

</html>