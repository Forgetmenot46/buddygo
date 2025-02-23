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
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
    :root {
        --primary-color: #4A90E2;
        --secondary-color: #F5F6F7;
        --accent-color: #2ECC71;
        --danger-color: #E74C3C;
        --text-color: #2C3E50;
        --border-radius: 15px;
        --shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Profile Card */
    .profile-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        background: linear-gradient(145deg, #ffffff, var(--secondary-color));
        transition: all 0.3s ease;
    }

    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    .profile-image {
        width: 150px;
        height: 150px;
        border: 4px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .profile-image:hover {
        transform: scale(1.05);
    }

    /* Information Card */
    .info-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        height: 100%;
        background: white;
    }

    .info-section {
        padding: 1.5rem;
    }

    .info-title {
        color: var(--text-color);
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-label {
        color: #7f8c8d;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    /* Badges */
    .badge-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .custom-badge {
        background-color: var(--primary-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .custom-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(74,144,226,0.3);
    }

    /* Buttons */
    .btn-edit-profile {
        background-color:rgb(190, 190, 190); /* สีน้ำเงินของ Bootstrap */
        color: white;
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-edit-profile:hover {
        background-color: #0b5ed7; /* สีน้ำเงินเข้มขึ้นเมื่อ hover */
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        color: white;
    }

    .btn-logout {
        background-color: #dc3545; /* สีแดงของ Bootstrap */
        color: white;
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        margin-left: 0.5rem;
    }

    .btn-logout:hover {
        background-color: #bb2d3b; /* สีแดงเข้มขึ้นเมื่อ hover */
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        color: white;
    }

    /* Username and Name */
    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-color);
        margin: 1rem 0 0.5rem;
    }

    .profile-username {
        color: #7f8c8d;
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .profile-image {
            width: 120px;
            height: 120px;
        }

        .btn-edit-profile, .btn-logout {
            width: 100%;
            margin: 0.5rem 0;
        }

        .info-section {
            padding: 1rem;
        }
    }

    /* Modal Styling */
    .modal-content {
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .modal-header {
        background-color: var(--primary-color);
        color: white;
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
        padding: 1rem 1.5rem;
    }

    .modal-title {
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-label {
        color: var(--text-color);
        font-weight: 500;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #dee2e6;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(74,144,226,0.25);
    }

    /* เพิ่มเอฟเฟกต์การโหลด */
    .loading {
        position: relative;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: inherit;
    }

    /* เพิ่มเอฟเฟกต์ tooltip */
    [data-tooltip] {
        position: relative;
        cursor: help;
    }

    [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 5px 10px;
        background: rgba(0,0,0,0.8);
        color: white;
        border-radius: 5px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
    }

    [data-tooltip]:hover::after {
        opacity: 1;
        visibility: visible;
        bottom: calc(100% + 5px);
    }

    /* เพิ่มเอฟเฟกต์การเลือก interest */
    .interest-tag {
        position: relative;
        overflow: hidden;
    }

    .interest-tag::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s ease, height 0.4s ease;
    }

    .interest-tag:active::before {
        width: 200px;
        height: 200px;
    }

    /* เพิ่มเอฟเฟกต์การแจ้งเตือน */
    @keyframes notification-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .notification-badge {
        animation: notification-pulse 2s infinite;
    }

    .avatar-option {
        cursor: pointer;
        border: 3px solid transparent;
        border-radius: 50%;
        padding: 2px;
        transition: all 0.2s ease;
    }

    .avatar-option:hover {
        transform: scale(1.1);
    }

    .avatar-option.selected {
        border-color: #0d6efd;
    }

    .avatar-option img {
        border-radius: 50%;
    }

    .avatar-select label {
        cursor: pointer;
        display: block;
        border: 3px solid transparent;
        border-radius: 50%;
        padding: 2px;
        transition: all 0.2s ease;
    }

    .avatar-select label:hover {
        transform: scale(1.1);
    }

    .avatar-select input[type="radio"]:checked + label {
        border-color: #0d6efd;
    }

    .avatar-img {
        border-radius: 50%;
    }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

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
                                <div class="d-flex justify-content-center flex-wrap">
                                    <button class="btn btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editProfileModal" 
                                            data-tooltip="แก้ไขข้อมูลส่วนตัวของคุณ">
                                        <i class="fas fa-edit me-2"></i>แก้ไขโปรไฟล์
                            </button>
                            <form action="logout.php" method="POST" class="d-inline" onsubmit="return confirmLogout();">
                                        <button type="submit" class="btn btn-logout" data-tooltip="ออกจากระบบ">
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

    <!-- เพิ่ม CSS -->
    <style>
    .default-avatars .avatar-option {
        cursor: pointer;
        padding: 4px;
        border: 2px solid transparent;
        border-radius: 50%;
        transition: all 0.2s;
    }

    .default-avatars .avatar-option:hover {
        border-color: #0d6efd;
    }

    .default-avatars .avatar-option.selected {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.1);
    }

    .default-avatars img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
    }

    .interest-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        min-height: 50px;
    }

    .interest-tag {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
    }

    .interest-tag:hover {
        background-color: #e9ecef;
    }

    .interest-tag.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    </style>

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

    <!-- เพิ่มส่วนแสดงกิจกรรมหลังจากส่วนข้อมูลโปรไฟล์ที่มีอยู่เดิม -->
    <div class="container mt-4">
        <div class="row justify-content-center" style="margin-right: -300px;">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">กิจกรรมของฉัน</h3>
                        <?php
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

                        try {
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result && $result->num_rows > 0):
                                while ($post = $result->fetch_assoc()):
                        ?>
                                <div class="card shadow-sm mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($post['title']); ?></h5>
                                        <p class="card-text text-muted mb-3"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>

                                        <?php if (!empty($post['interests'])): ?>
                                            <div class="mb-3">
                                                <?php foreach (explode(',', $post['interests']) as $interest): ?>
                                                    <span class="badge rounded-pill bg-secondary me-1">
                                                        <?php echo htmlspecialchars($interest); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                <?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?> คน
                                                <span class="mx-2">•</span>
                                                <i class="far fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($post['activity_date'])); ?>
                                                <span class="mx-2">•</span>
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('H:i', strtotime($post['activity_time'])); ?> น.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php 
                                endwhile;
                            else:
                        ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    คุณยังไม่มีกิจกรรม
                                </div>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูล</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>