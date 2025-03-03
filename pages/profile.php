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

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// เรียกใช้ฟังก์ชัน displayAd3 ถ้าผู้ใช้ไม่ใช่ admin
if ($user['is_admin'] != 1 && $user['role'] != 'admin') {
    displayAd3($user['is_admin']);
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
                                    <h3 class="profile-name">
                                        <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                        <?php if ($user['verified_status'] == 1): ?>
                                            <i class="fas fa-check-circle text-primary" title="Verified User"></i>
                                        <?php endif; ?>
                                    </h3>
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


            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function confirmChange() {
                    return confirm("คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนชื่อและนามสกุล?"); // Show confirmation alert
                }
            </script>
        </div>


    </div>

    <!-- ส่วนแสดงข้อมูลสำหรับ Admin -->
    <?php if ($user['is_admin'] == 1 || $user['role'] == 'admin'): ?>
       
        <div class="container mt-4">
            <div class="admin-dashboard">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>แผงควบคุมสำหรับผู้ดูแลระบบ</h4>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">ภาพรวม</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">ผู้ใช้งาน</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab" aria-controls="posts" aria-selected="false">โพสต์</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="interests-tab" data-bs-toggle="tab" data-bs-target="#interests" type="button" role="tab" aria-controls="interests" aria-selected="false">ความสนใจ</button>
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo isset($active_tab) && $active_tab === 'reports' ? 'active' : ''; ?>" 
           href="?tab=reports">
            <i class="fas fa-flag"></i> รายงานผู้ใช้
            <?php
            // นับจำนวนรายงานที่รอดำเนินการ
            $pending_reports_sql = "SELECT COUNT(*) as count FROM user_reports WHERE status = 'pending'";
            $pending_reports_result = $conn->query($pending_reports_sql);
            $pending_count = $pending_reports_result->fetch_assoc()['count'];
            if ($pending_count > 0):
            ?>
                <span class="badge bg-danger"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
    </li>
<?php endif; ?>

                        </ul>

                        <div class="tab-content p-3" id="adminTabContent">
                            
                            <!-- ภาพรวม -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">สถิติผู้ใช้งาน</h5>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="userStatsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">สถิติโพสต์</h5>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="postStatsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">สรุปข้อมูลระบบ</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                // ดึงข้อมูลสรุปจากฐานข้อมูล
                                                $stats_sql = "SELECT 
                                                (SELECT COUNT(*) FROM users) as total_users,
                                                (SELECT COUNT(*) FROM community_posts) as total_posts,
                                                (SELECT COUNT(*) FROM post_members) as total_participations,
                                                (SELECT COUNT(*) FROM interests) as total_interests";
                                                $stats_result = $conn->query($stats_sql);
                                                $stats = $stats_result->fetch_assoc();
                                                ?>
                                                <div class="row">
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <div class="stats-item text-center">
                                                            <div class="stats-icon bg-primary text-white rounded-circle mb-2">
                                                                <i class="fas fa-users"></i>
                                                            </div>
                                                            <h3><?php echo $stats['total_users']; ?></h3>
                                                            <p class="text-muted">ผู้ใช้งานทั้งหมด</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <div class="stats-item text-center">
                                                            <div class="stats-icon bg-success text-white rounded-circle mb-2">
                                                                <i class="fas fa-file-alt"></i>
                                                            </div>
                                                            <h3><?php echo $stats['total_posts']; ?></h3>
                                                            <p class="text-muted">โพสต์ทั้งหมด</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <div class="stats-item text-center">
                                                            <div class="stats-icon bg-info text-white rounded-circle mb-2">
                                                                <i class="fas fa-handshake"></i>
                                                            </div>
                                                            <h3><?php echo $stats['total_participations']; ?></h3>
                                                            <p class="text-muted">การเข้าร่วมทั้งหมด</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <div class="stats-item text-center">
                                                            <div class="stats-icon bg-warning text-white rounded-circle mb-2">
                                                                <i class="fas fa-tags"></i>
                                                            </div>
                                                            <h3><?php echo $stats['total_interests']; ?></h3>
                                                            <p class="text-muted">ความสนใจทั้งหมด</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ผู้ใช้งาน -->
                            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">รายชื่อผู้ใช้งาน</h5>
                                        <div class="input-group" style="max-width: 300px;">
                                            <input type="text" id="userSearchInput" class="form-control" placeholder="ค้นหาผู้ใช้...">
                                            <button class="btn btn-outline-secondary" type="button">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="usersTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>ชื่อผู้ใช้</th>
                                                        <th>อีเมล</th>
                                                        <th>สถานะ</th>
                                                        <th>วันที่สมัคร</th>
                                                        <th>เข้าสู่ระบบล่าสุด</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $users_sql = "SELECT id, username, email, verified_status, created_at, last_login FROM users ORDER BY id DESC LIMIT 10";
                                                    $users_result = $conn->query($users_sql);
                                                    while ($user_row = $users_result->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $user_row['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user_row['email']); ?></td>
                                                            <td>
                                                                <?php if ($user_row['verified_status'] == 1): ?>
                                                                    <span class="badge bg-success">ยืนยันแล้ว</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">ยังไม่ยืนยัน</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('d/m/Y', strtotime($user_row['created_at'])); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($user_row['last_login'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- โพสต์ -->
                            <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">โพสต์ยอดนิยม</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>หัวข้อ</th>
                                                        <th>ผู้โพสต์</th>
                                                        <th>ยอดเข้าชม</th>
                                                        <th>ผู้เข้าร่วม</th>
                                                        <th>วันที่โพสต์</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $posts_sql = "SELECT p.post_id, p.title, p.view_count, p.created_at, u.username,
                                                            (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status IN ('joined', 'confirmed')) as joined_count
                                                            FROM community_posts p
                                                            JOIN users u ON p.user_id = u.id
                                                            ORDER BY p.view_count DESC
                                                            LIMIT 10";
                                                    $posts_result = $conn->query($posts_sql);
                                                    while ($post_row = $posts_result->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $post_row['post_id']; ?></td>
                                                            <td><?php echo htmlspecialchars($post_row['title']); ?></td>
                                                            <td><?php echo htmlspecialchars($post_row['username']); ?></td>
                                                            <td><?php echo $post_row['view_count']; ?></td>
                                                            <td><?php echo $post_row['joined_count']; ?></td>
                                                            <td><?php echo date('d/m/Y', strtotime($post_row['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ความสนใจ -->
                            <div class="tab-pane fade" id="interests" role="tabpanel" aria-labelledby="interests-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">ความสนใจยอดนิยม</h5>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="interestsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">รายการความสนใจ</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>ชื่อความสนใจ</th>
                                                                <th>จำนวนผู้ใช้</th>
                                                                <th>จำนวนโพสต์</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $interests_sql = "SELECT i.id, i.interest_name,
                                                                        (SELECT COUNT(*) FROM user_interests WHERE interest_id = i.id) as user_count,
                                                                        (SELECT COUNT(*) FROM post_interests WHERE interest_id = i.id) as post_count
                                                                        FROM interests i
                                                                        ORDER BY user_count DESC
                                                                        LIMIT 10";
                                                            $interests_result = $conn->query($interests_sql);
                                                            while ($interest_row = $interests_result->fetch_assoc()):
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $interest_row['id']; ?></td>
                                                                    <td><?php echo htmlspecialchars($interest_row['interest_name']); ?></td>
                                                                    <td><?php echo $interest_row['user_count']; ?></td>
                                                                    <td><?php echo $interest_row['post_count']; ?></td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- รายงานผู้ใช้ -->
                            <?php if ($active_tab === 'reports' && $_SESSION['role'] === 'admin'): ?>
                                <div class="tab-pane fade show active" id="reports">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">รายงานผู้ใช้</h5>
                                            <a href="admin/manage_reports.php" class="btn btn-primary btn-sm">
                                                <i class="fas fa-cog"></i> จัดการรายงานทั้งหมด
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            // ดึงรายงานล่าสุด 5 รายการ
                                            $recent_reports_sql = "SELECT r.*, 
                                                                        u1.username as reported_username,
                                                                        u2.username as reporter_username,
                                                                        (SELECT COUNT(*) FROM user_reports WHERE reported_user_id = r.reported_user_id) as total_reports
                                                                 FROM user_reports r
                                                                 JOIN users u1 ON r.reported_user_id = u1.id
                                                                 JOIN users u2 ON r.reporting_user_id = u2.id
                                                                 WHERE r.status = 'pending'
                                                                 ORDER BY r.created_at DESC
                                                                 LIMIT 5";
                                            $recent_reports = $conn->query($recent_reports_sql);
                                            
                                            if ($recent_reports->num_rows > 0):
                                                while ($report = $recent_reports->fetch_assoc()):
                                            ?>
                                                <div class="card mb-3 border-warning">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1">
                                                                    ผู้ถูกรายงาน: <?php echo htmlspecialchars($report['reported_username']); ?>
                                                                    <span class="badge bg-warning ms-2">
                                                                        รายงานทั้งหมด: <?php echo $report['total_reports']; ?>
                                                                    </span>
                                                                </h6>
                                                                <p class="mb-1">
                                                                    <small class="text-muted">
                                                                        รายงานโดย: <?php echo htmlspecialchars($report['reporter_username']); ?>
                                                                            | วันที่: <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?>
                                                                    </small>
                                                                </p>
                                                                <p class="mb-2">
                                                                    <span class="badge bg-primary">
                                                                        <?php 
                                                                        switch($report['violation_type']) {
                                                                            case 'no_show': echo 'ไม่มาตามนัด'; break;
                                                                            case 'harassment': echo 'การคุกคาม/ก่อกวน'; break;
                                                                            case 'inappropriate': echo 'พฤติกรรมไม่เหมาะสม'; break;
                                                                            case 'scam': echo 'การหลอกลวง'; break;
                                                                            default: echo 'อื่นๆ';
                                                                        }
                                                                        ?>
                                                                    </span>
                                                                </p>
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                                            </div>
                                                            <a href="admin/manage_reports.php" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-external-link-alt"></i> ดูรายละเอียด
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted">ไม่มีรายงานที่รอดำเนินการ</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- เพิ่ม CSS สำหรับ Admin Dashboard -->
        <style>
            .admin-dashboard {
                margin-bottom: 30px;
            }

            .stats-icon {
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                font-size: 20px;
            }

            .stats-item h3 {
                font-size: 24px;
                font-weight: bold;
                margin: 10px 0 5px;
            }

            .nav-tabs .nav-link {
                color: #495057;
            }

            .nav-tabs .nav-link.active {
                font-weight: bold;
                color: #0d6efd;
            }
        </style>

        <!-- เพิ่ม Chart.js สำหรับแสดงกราฟ -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- JavaScript สำหรับ Admin Dashboard -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // กราฟสถิติผู้ใช้งาน
                const userCtx = document.getElementById('userStatsChart').getContext('2d');
                const userStatsChart = new Chart(userCtx, {
                    type: 'line',
                    data: {
                        labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                        datasets: [{
                            label: 'ผู้ใช้ใหม่',
                            data: [
                                <?php
                                // ดึงข้อมูลผู้ใช้ใหม่รายเดือน
                                $user_stats = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $month_sql = "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = $i AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                                    $month_result = $conn->query($month_sql);
                                    $user_stats[] = $month_result->fetch_assoc()['count'];
                                }
                                echo implode(',', $user_stats);
                                ?>
                            ],
                            borderColor: 'rgba(13, 110, 253, 1)',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'ผู้ใช้ใหม่รายเดือน'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // กราฟสถิติโพสต์
                const postCtx = document.getElementById('postStatsChart').getContext('2d');
                const postStatsChart = new Chart(postCtx, {
                    type: 'bar',
                    data: {
                        labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                        datasets: [{
                            label: 'โพสต์ใหม่',
                            data: [
                                <?php
                                // ดึงข้อมูลโพสต์ใหม่รายเดือน
                                $post_stats = [];
                                for ($i = 1; $i <= 12; $i++) {
                                    $month_sql = "SELECT COUNT(*) as count FROM community_posts WHERE MONTH(created_at) = $i AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                                    $month_result = $conn->query($month_sql);
                                    $post_stats[] = $month_result->fetch_assoc()['count'];
                                }
                                echo implode(',', $post_stats);
                                ?>
                            ],
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'โพสต์ใหม่รายเดือน'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // กราฟความสนใจยอดนิยม
                const interestsCtx = document.getElementById('interestsChart').getContext('2d');
                const interestsChart = new Chart(interestsCtx, {
                    type: 'pie',
                    data: {
                        labels: [
                            <?php
                            // ดึงข้อมูลความสนใจยอดนิยม
                            $top_interests_sql = "SELECT i.interest_name, COUNT(ui.user_id) as user_count
                                            FROM interests i
                                            JOIN user_interests ui ON i.id = ui.interest_id
                                            GROUP BY i.id
                                            ORDER BY user_count DESC
                                            LIMIT 5";
                            $top_interests_result = $conn->query($top_interests_sql);
                            $interest_names = [];
                            $interest_counts = [];
                            while ($interest = $top_interests_result->fetch_assoc()) {
                                $interest_names[] = "'" . htmlspecialchars($interest['interest_name']) . "'";
                                $interest_counts[] = $interest['user_count'];
                            }
                            echo implode(',', $interest_names);
                            ?>
                        ],
                        datasets: [{
                            data: [<?php echo implode(',', $interest_counts); ?>],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            title: {
                                display: true,
                                text: 'ความสนใจยอดนิยม'
                            }
                        }
                    }
                });

                // ค้นหาผู้ใช้
                document.getElementById('userSearchInput').addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('usersTable');
                    const rows = table.getElementsByTagName('tr');

                    for (let i = 1; i < rows.length; i++) {
                        const username = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                        const email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();

                        if (username.includes(searchValue) || email.includes(searchValue)) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>