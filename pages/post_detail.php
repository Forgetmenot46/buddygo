<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามีการส่ง post_id มาหรือไม่
if (!isset($_GET['post_id'])) {
    header("Location: index.php");
    exit();
}

$post_id = $_GET['post_id'];

// ดึงข้อมูลโพสต์และข้อมูลที่เกี่ยวข้องทั้งหมด
$post_sql = "SELECT p.*, u.username, u.profile_picture,
             GROUP_CONCAT(DISTINCT i.interest_name) as interests,
             (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'joined') as current_members
             FROM community_posts p 
             JOIN users u ON p.user_id = u.id 
             LEFT JOIN post_interests pi ON p.post_id = pi.post_id
             LEFT JOIN interests i ON pi.interest_id = i.id
             WHERE p.post_id = ?
             GROUP BY p.post_id";
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post = $post_stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - BuddyGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/profilestyle.css" rel="stylesheet">

    <style>
        .post-detail-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .interest-tag {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .qr-code-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 10px;
            background: white;
            border: 1px solid #dee2e6;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>

<body>
    <div class="row">
        <!-- คอลัมน์ซ้าย (Sidebar) -->
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <!-- คอลัมน์หลัก -->
        <div class="col-12 col-md-8 mt-4">
            <div class="post-detail-card card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo getProfileImage($post['user_id']); ?>"
                                class="rounded-circle me-3"
                                style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h2 class="mb-0"><?php echo htmlspecialchars($post['title']); ?></h2>
                                <small class="text-muted">โดย <?php echo htmlspecialchars($post['username']); ?></small>
                            </div>
                        </div>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปหน้าหลัก
                        </a>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">รายละเอียดกิจกรรม</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">ข้อมูลการจัดกิจกรรม</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-calendar me-2 text-primary"></i>
                                    วันที่: <?php echo date('d/m/Y', strtotime($post['activity_date'])); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    เวลา: <?php echo date('H:i', strtotime($post['activity_time'])); ?> น.
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-users me-2 text-primary"></i>
                                    จำนวนคน: <?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?> คน
                                </li>
                                <li>
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                    สถานที่: <?php echo htmlspecialchars($post['post_local']); ?>
                                </li>
                            </ul>
                        </div>

                        <?php if (!empty($post['interests'])): ?>
                            <p class="card-text">
                                <small class="text-muted">กิจกรรม:
                                    <div class="interest-tags" >
                                        <?php
                                        if (!empty($post['interests'])) {
                                            // แยกกิจกรรมที่มีหลายรายการออกมา
                                            $interests_array = explode(',', $post['interests']);
                                            foreach ($interests_array as $interest) {
                                                echo '<span class="custom-badge">' . htmlspecialchars($interest) . '</span> ';
                                            }
                                        } else {
                                            echo "ไม่มี";
                                        }
                                        ?>
                                    </div>
                                </small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>