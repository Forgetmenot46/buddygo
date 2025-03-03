<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// แสดงโฆษณาทันทีเมื่อผู้ใช้ล็อกอิน


// กำหนดจำนวนโพสต์ต่อหน้า
$posts_per_page = 6;

// คำนวณหน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // ป้องกันกรณีค่าต่ำกว่า 1
$offset = ($current_page - 1) * $posts_per_page;

// ดึงข้อมูลกิจกรรมที่ผู้ใช้สนใจ
$user_interests = [];
$user_interests_sql = "SELECT interest_id FROM user_interests WHERE user_id = ?";
$user_interests_stmt = $conn->prepare($user_interests_sql);
$user_interests_stmt->bind_param("i", $user_id);
$user_interests_stmt->execute();
$user_interests_result = $user_interests_stmt->get_result();
while ($interest = $user_interests_result->fetch_assoc()) {
    $user_interests[] = $interest['interest_id'];
}

// นับจำนวนโพสต์ทั้งหมดเพื่อคำนวณจำนวนหน้า
$count_sql = "SELECT COUNT(DISTINCT p.post_id) as total 
              FROM community_posts p 
              WHERE p.status = 'active'";
$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_posts = $count_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// ดึงโพสต์โดยใช้ LIMIT และ OFFSET สำหรับการแบ่งหน้า
$posts_sql = "SELECT p.*, p.view_count, u.username as creator_name, u.verified_status as creator_verified_status,
    GROUP_CONCAT(DISTINCT i.interest_name) as interests,
    GROUP_CONCAT(DISTINCT i.id) as interest_ids,
    (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'joined') as current_members,
    (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'interested') as interested_count,
    (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'confirmed') as confirmed_count,
    pm.status as member_status
FROM community_posts p
JOIN users u ON p.user_id = u.id
LEFT JOIN post_interests pi ON p.post_id = pi.post_id
LEFT JOIN interests i ON pi.interest_id = i.id
LEFT JOIN post_members pm ON p.post_id = pm.post_id AND pm.user_id = ?
WHERE p.status = 'active' 
GROUP BY p.post_id
ORDER BY p.created_at DESC
LIMIT ? OFFSET ?";

$posts_stmt = $conn->prepare($posts_sql);
$posts_stmt->bind_param("iii", $_SESSION['user_id'], $posts_per_page, $offset);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

// เพิ่มฟังก์ชันนับจำนวนการเข้าชมโพสต์

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo - หน้าแรก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/profilestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/indexstyle.css">
    <style>
        body,
        html {
            overflow-x: hidden;
        }

        .dropdown-menu {
            min-width: 200px;
            z-index: 1021;
        }

        .dropdown-toggle::after {
            display: none;
        }

        .post-actions {
            position: relative;
            z-index: 1020;
        }

        .card {
            position: relative;
        }

        .dropdown-item {
            cursor: pointer;
        }

        .btn-link {
            text-decoration: none;
        }

        /* ทำให้ dropdown menu อยู่ด้านขวาของปุ่ม */
        .dropdown-menu-end {
            right: 0;
            left: auto;
        }

        /* เพิ่ม CSS สำหรับการ์ดโพสต์ใหม่ */
        .hover-shadow {
            transition: all 0.3s ease;
        }

        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .custom-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 75%;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10rem;
            background-color: #f8f9fa;
            color: #6c757d;
            margin-right: 0.3rem;
        }

        .post-card {
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
        }

        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .transition {
            transition: all 0.3s ease;
        }

        .pagination .page-item .page-link {
            color: #444;
            border-radius: 50%;
            margin: 0 3px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .pagination .page-item .page-link:hover {
            background-color: rgb(124, 124, 124);
        }

        .pagination .page-item.disabled .page-link {
            color: rgb(138, 138, 138);
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* เพิ่ม animation เมื่อ hover */
        .btn-outline-danger:hover {
            transform: scale(1.1);
            transition: transform 0.2s;
        }

        .btn-outline-primary:hover {
            transform: scale(1.1);
            transition: transform 0.2s;
        }

        /* สไตล์สำหรับปุ่มลบ */
        .btn-danger.rounded-circle {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-danger.rounded-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
        }

        .ad-container {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }

        .ad-container img {
            max-width: 100%;
            height: auto;
        }

        .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .popular-posts {
            margin-top: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: box-shadow 0.3s;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .card-text {
            margin-bottom: 10px;
        }

        .popular-posts-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .popular-post-card {
            transition: transform 0.2s;
            border-radius: 6px;
        }

        .popular-post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .popular-post-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .popular-post-card small {
            font-size: 0.75rem;
        }

        .popular-post-card .btn-sm {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
        }

        /* CSS สำหรับ pop-up */
        .popup {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            animation: fadeIn 0.3s ease-in-out;
        }

        .popup-content {
            background-color: white;
            margin: 5% auto;
            padding: 25px;
            border-radius: 15px;
            width: 80%;
            max-width: 800px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease-out;
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .popup-header h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 32px;
            color: #666;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f8f8;
        }

        .close:hover {
            color: #333;
            background-color: #eee;
            transform: rotate(90deg);
        }

        .popup-image-container {
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .popup-image-container img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .popup-image-container img:hover {
            transform: scale(1.02);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .popup-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .popup-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .popup-button.close-btn {
            background-color: #f8f8f8;
            color: #333;
        }

        .popup-button.close-btn:hover {
            background-color: #eee;
        }
    </style>
</head>

<body>
    <?php
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    // ตรวจสอบการล็อกอิน
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    ?>

    <div class="row ">
        <!-- คอลัมน์ซ้าย (Sidebar) -->
        <div class="col-2 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <!-- คอลัมน์กลาง (โพสต์กิจกรรม) -->
        <div class="col-6 col-md-6 offset-md-1 mt-5"> <!-- เพิ่ม offset-lg-1 เพื่อเว้นระยะจาก sidebar -->
            <div class="d-flex justify-content-between mb-4 mt-3 gap-2">
                <a href="Create_Activity.php" class="btn btn-primary justify-content-center flex-grow-1">
                    <i class="fas fa-plus me-2"></i>สร้างกิจกรรมใหม่
                </a>
                <button type="button"
                    class="btn btn-outline-danger"
                    id="toggleDeleteButton"
                    onclick="toggleDeleteButtons()">
                    <i class="fas fa-trash-alt me-2"></i>ลบโพสต์
                </button>
            </div>

            <!-- รายการโพสต์ -->
            <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($post = $posts_result->fetch_assoc()): ?>
                        <div class="col-12 mb-4"> <!-- เปลี่ยนจาก col-md-6 col-lg-4 เป็น col-12 -->
                            <div class="card post-card h-100 shadow-sm hover-shadow transition"
                                onclick="viewPost(<?php echo $post['post_id']; ?>, event)">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <!-- รูปโปรไฟล์ -->
                                            <img src="<?php echo getProfileImage($post['user_id']); ?>"
                                                class="rounded-circle me-2"
                                                style="width: 36px; height: 36px; object-fit: cover;">
                                            <div>
                                                <small class="text-muted d-block">โดย <?php echo htmlspecialchars($post['creator_name']); ?><?php echo getVerifiedIcon($post['creator_verified_status']); ?></small>
                                            </div>
                                        </div>

                                        <!-- เมนูการดำเนินการ (ถ้าเป็นผู้โพสต์) -->
                                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                            <button type="button"
                                                class="btn btn-danger btn-sm rounded-circle delete-button"
                                                style="display: none;"
                                                onclick="event.stopPropagation(); deletePost(<?php echo $post['post_id']; ?>);"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="ลบโพสต์">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-body pb-2">
                                    <!-- หัวข้อโพสต์ -->
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($post['title']); ?></h5>

                                    <!-- รายละเอียดโพสต์ -->
                                    <p class="card-text text-truncate mb-2"><?php echo htmlspecialchars($post['description']); ?></p>

                                    <!-- ข้อมูลกิจกรรมแบบไอคอน -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="far fa-calendar-alt text-primary me-2"></i>
                                            <small><?php echo $post['activity_date']; ?></small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            <small class="text-truncate"><?php echo htmlspecialchars($post['post_local']); ?></small>
                                        </div>
                                    </div>

                                    <!-- แสดงแท็กกิจกรรม -->
                                    <div class="mb-2">
                                        <?php
                                        if (!empty($post['interests'])) {
                                            $interests_array = explode(',', $post['interests']);
                                            $max_tags = 3;
                                            $more_count = count($interests_array) - $max_tags;

                                            for ($i = 0; $i < min(count($interests_array), $max_tags); $i++) {
                                                echo '<span class="badge bg-light text-secondary rounded-pill me-1 mb-1 px-2 py-1">' .
                                                    htmlspecialchars(trim($interests_array[$i])) . '</span>';
                                            }

                                            if ($more_count > 0) {
                                                echo '<span class="badge bg-light text-secondary rounded-pill px-2 py-1">+' . $more_count . ' อื่นๆ</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="far fa-hand-point-up text-primary me-1"></i>
                                        <small class="text-muted"><?php echo isset($post['interested_count']) ? $post['interested_count'] : 0; ?> สนใจ</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users text-success me-1"></i>
                                        <small class="text-muted"><?php echo isset($post['confirmed_count']) ? $post['confirmed_count'] : $post['current_members']; ?> เข้าร่วม</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="far fa-eye text-info me-1"></i>
                                        <small class="text-muted"><?php echo isset($post['view_count']) ? $post['view_count'] : 0; ?> เข้าชม</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- ระบบ Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- ปุ่มก่อนหน้า -->
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                                </a>
                            </li>

                            <?php
                            // จำนวนปุ่มหน้าที่จะแสดงทั้งหมด
                            $visible_pages = 5;
                            $half_total_links = floor($visible_pages / 2);

                            // คำนวณช่วงหน้าที่จะแสดง
                            $start_page = max(1, $current_page - $half_total_links);
                            $end_page = min($total_pages, $current_page + $half_total_links);

                            // ปรับค่าเริ่มต้นและสิ้นสุดเพื่อให้แสดงจำนวนปุ่มตามที่กำหนด
                            if ($end_page - $start_page + 1 < $visible_pages) {
                                if ($start_page == 1) {
                                    $end_page = min($visible_pages, $total_pages);
                                } else {
                                    $start_page = max(1, $end_page - $visible_pages + 1);
                                }
                            }

                            // แสดงปุ่มหน้าแรกถ้าหน้าเริ่มต้นไม่ใช่หน้า 1
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                                }
                            }

                            // แสดงปุ่มหน้าตามช่วงที่คำนวณ
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . (($i == $current_page) ? 'active' : '') . '">
                                    <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                                  </li>';
                            }

                            // แสดงปุ่มหน้าสุดท้ายถ้าหน้าสุดท้ายไม่อยู่ในช่วงที่แสดง
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <!-- ปุ่มถัดไป -->
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center my-4">
                    <i class="fas fa-info-circle me-2"></i>ไม่พบโพสต์ในขณะนี้
                </div>
            <?php endif; ?>
        </div>
        <div class="col-2 col-md-2 mt-5">


            <!-- เริ่มส่วนแสดงโพสต์ยอดนิยมประจำเดือน -->
            <div class="popular-posts-container mt-4">
                <h5 class="text-center mb-3">ยอดนิยมประจำเดือน <?php echo date('F Y'); ?></h5>

                <?php
                // กำหนดเดือนและปีปัจจุบัน
                $current_month = date('n');
                $current_year = date('Y');

                // 1. โพสต์ที่มียอดวิวเยอะที่สุด 1 อันดับ
                $most_viewed_sql = "
                    SELECT 
                        p.post_id,
                        p.title,
                        p.user_id,
                        p.view_count,
                        u.username as creator_name
                    FROM 
                        community_posts p
                    JOIN 
                        users u ON p.user_id = u.id
                    WHERE 
                        p.status = 'active' AND
                        MONTH(p.created_at) = ? AND
                        YEAR(p.created_at) = ?
                    ORDER BY 
                        p.view_count DESC
                    LIMIT 1
                ";

                $most_viewed_stmt = $conn->prepare($most_viewed_sql);
                $most_viewed_stmt->bind_param("ii", $current_month, $current_year);
                $most_viewed_stmt->execute();
                $most_viewed_result = $most_viewed_stmt->get_result();
                $most_viewed_post = $most_viewed_result->fetch_assoc();

                // 2. โพสต์ที่มียอดเข้าร่วมเยอะที่สุด 1 อันดับ
                $most_joined_sql = "
                    SELECT 
                        p.post_id,
                        p.title,
                        p.user_id,
                        COUNT(DISTINCT pm.user_id) as joined_count,
                        u.username as creator_name
                    FROM 
                        community_posts p
                    JOIN 
                        users u ON p.user_id = u.id
                    LEFT JOIN 
                        post_members pm ON p.post_id = pm.post_id AND pm.status IN ('confirmed', 'joined')
                    WHERE 
                        p.status = 'active' AND
                        MONTH(p.created_at) = ? AND
                        YEAR(p.created_at) = ?
                    GROUP BY 
                        p.post_id
                    ORDER BY 
                        joined_count DESC
                    LIMIT 1
                ";

                $most_joined_stmt = $conn->prepare($most_joined_sql);
                $most_joined_stmt->bind_param("ii", $current_month, $current_year);
                $most_joined_stmt->execute();
                $most_joined_result = $most_joined_stmt->get_result();
                $most_joined_post = $most_joined_result->fetch_assoc();

                // 3. แท็กยอดนิยม 3 อันดับ
                $popular_tags_sql = "
                    SELECT 
                        i.id,
                        i.interest_name,
                        COUNT(pi.post_id) as post_count
                    FROM 
                        interests i
                    JOIN 
                        post_interests pi ON i.id = pi.interest_id
                    JOIN 
                        community_posts p ON pi.post_id = p.post_id
                    WHERE 
                        p.status = 'active' AND
                        MONTH(p.created_at) = ? AND
                        YEAR(p.created_at) = ?
                    GROUP BY 
                        i.id
                    ORDER BY 
                        post_count DESC
                    LIMIT 3
                ";

                $popular_tags_stmt = $conn->prepare($popular_tags_sql);
                $popular_tags_stmt->bind_param("ii", $current_month, $current_year);
                $popular_tags_stmt->execute();
                $popular_tags_result = $popular_tags_stmt->get_result();
                ?>

                <!-- แสดงโพสต์ที่มียอดวิวเยอะที่สุด -->
                <div class="popular-section mb-3">
                    <h6 class="popular-section-title">
                        <i class="far fa-eye text-info me-1"></i> ยอดวิวสูงสุด
                    </h6>
                    <?php if ($most_viewed_post): ?>
                        <div class="card popular-post-card">
                            <div class="card-body p-2">
                                <h6 class="card-title text-truncate"><?php echo htmlspecialchars($most_viewed_post['title']); ?></h6>
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?php echo getProfileImage($most_viewed_post['user_id']); ?>"
                                        class="rounded-circle me-1"
                                        style="width: 24px; height: 24px; object-fit: cover;">
                                    <small class="text-muted">โดย <?php echo htmlspecialchars($most_viewed_post['creator_name']); ?></small>
                                </div>
                                <div class="text-center mb-2">
                                    <span class="badge bg-info">
                                        <i class="far fa-eye me-1"></i> <?php echo $most_viewed_post['view_count']; ?> ครั้ง
                                    </span>
                                </div>
                                <a href="post_detail.php?post_id=<?php echo $most_viewed_post['post_id']; ?>"
                                    class="btn btn-sm btn-outline-primary w-100">ดูรายละเอียด</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2">
                            <small>ไม่พบข้อมูล</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- แสดงโพสต์ที่มียอดเข้าร่วมเยอะที่สุด -->
                <div class="popular-section mb-3">
                    <h6 class="popular-section-title">
                        <i class="fas fa-users text-success me-1"></i> ยอดเข้าร่วมสูงสุด
                    </h6>
                    <?php if ($most_joined_post): ?>
                        <div class="card popular-post-card">
                            <div class="card-body p-2">
                                <h6 class="card-title text-truncate"><?php echo htmlspecialchars($most_joined_post['title']); ?></h6>
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?php echo getProfileImage($most_joined_post['user_id']); ?>"
                                        class="rounded-circle me-1"
                                        style="width: 24px; height: 24px; object-fit: cover;">
                                    <small class="text-muted">โดย <?php echo htmlspecialchars($most_joined_post['creator_name']); ?></small>
                                </div>
                                <div class="text-center mb-2">
                                    <span class="badge bg-success">
                                        <i class="fas fa-users me-1"></i> <?php echo $most_joined_post['joined_count']; ?> คน
                                    </span>
                                </div>
                                <a href="post_detail.php?post_id=<?php echo $most_joined_post['post_id']; ?>"
                                    class="btn btn-sm btn-outline-primary w-100">ดูรายละเอียด</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2">
                            <small>ไม่พบข้อมูล</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- แสดงแท็กยอดนิยม 3 อันดับ -->
                <div class="popular-section">
                    <h6 class="popular-section-title">
                        <i class="fas fa-tags text-warning me-1"></i> แท็กยอดนิยม
                    </h6>
                    <?php if ($popular_tags_result && $popular_tags_result->num_rows > 0): ?>
                        <div class="popular-tags">
                            <?php
                            $tag_colors = ['primary', 'success', 'danger'];
                            $tag_index = 0;
                            while ($tag = $popular_tags_result->fetch_assoc()):
                                $color = $tag_colors[$tag_index % count($tag_colors)];
                            ?>
                                <div class="popular-tag-item mb-2">
                                    <span class="badge bg-<?php echo $color; ?> me-2">#<?php echo $tag_index + 1; ?></span>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($tag['interest_name']); ?>
                                        <span class="ms-1 text-<?php echo $color; ?>">(<?php echo $tag['post_count']; ?>)</span>
                                    </span>
                                </div>
                            <?php
                                $tag_index++;
                            endwhile;
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2">
                            <small>ไม่พบข้อมูล</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- จบส่วนแสดงโพสต์ยอดนิยม -->
            <?php displayAd(); ?>
            <?php displayAd2(); ?>
        </div>
    </div>

    <div id="adPopup" class="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h2>โฆษณา</h2>
                <span class="close" onclick="closeAdPopup()">&times;</span>
            </div>
            <div class="popup-image-container">
                <?php
                // เรียกใช้ฟังก์ชันเพื่อแสดงโฆษณาแบบสุ่ม
                $adsDir = '../assets/images/squre-ads/';
                $ads = glob($adsDir . '*.jpg');
                if (!empty($ads)) {
                    $randomAd = $ads[array_rand($ads)];
                    echo '<img src="' . htmlspecialchars($randomAd) . '" alt="Ad" class="img-fluid">';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        <?php if (isset($_SESSION['alert'])): ?>
            alert('<?php echo addslashes($_SESSION['alert']['message']); ?>');
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function deletePost(postId) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบโพสต์นี้?')) {
                fetch('delete_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'post_id=' + postId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // ถ้าลบสำเร็จ ให้รีโหลดหน้า
                            location.reload();
                        } else {
                            // ถ้าไม่สำเร็จ ให้แสดงข้อความผิดพลาด
                            alert(data.message || 'เกิดข้อผิดพลาดในการลบโพสต์');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาดในการลบโพสต์');
                    });
            }
        }

        // เพิ่ม event listener เมื่อ document โหลดเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            // ป้องกันการ redirect เมื่อคลิกที่ dropdown
            const cards = document.querySelectorAll('.card[style*="cursor: pointer"]');
            cards.forEach(card => {
                const actions = card.querySelector('.post-actions');
                if (actions) {
                    actions.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            });

            // ทำให้ dropdown ทำงานได้
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'))
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            });
        });

        function toggleDeleteButtons() {
            const deleteButtons = document.querySelectorAll('.delete-button');
            const toggleBtn = document.getElementById('toggleDeleteButton');

            deleteButtons.forEach(button => {
                if (button.style.display === 'none') {
                    button.style.display = 'flex';
                    toggleBtn.classList.remove('btn-outline-danger');
                    toggleBtn.classList.add('btn-danger');
                } else {
                    button.style.display = 'none';
                    toggleBtn.classList.remove('btn-danger');
                    toggleBtn.classList.add('btn-outline-danger');
                }
            });
        }

        // เพิ่มฟังก์ชันสำหรับเข้าชมโพสต์
        function viewPost(postId, event) {
            // ป้องกันการทำงานซ้ำซ้อนถ้ามีการคลิกที่ปุ่มลบ
            if (event.target.closest('.delete-button')) {
                return;
            }

            // บันทึกการเข้าชม
            fetch('increment_post_view.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'post_id=' + postId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // นำทางไปยังหน้ารายละเอียดโพสต์
                        window.location.href = 'post_detail.php?post_id=' + postId;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // ถึงแม้จะมีข้อผิดพลาด ก็ยังนำทางไปยังหน้ารายละเอียดโพสต์
                    window.location.href = 'post_detail.php?post_id=' + postId;
                });
        }

        function closeAdPopup() {
            const popup = document.getElementById('adPopup');
            popup.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                popup.style.display = 'none';
                popup.style.animation = '';
            }, 300);
        }

        // แสดง pop-up เมื่อโหลดหน้า
        window.onload = function() {
            const adPopup = document.getElementById('adPopup');
            setTimeout(() => {
                adPopup.style.display = 'block';
            }, 500); // แสดง pop-up หลังจากโหลดหน้าเสร็จ 500ms
        };

        // เพิ่ม event listener สำหรับการปิด pop-up เมื่อคลิกพื้นหลัง
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('adPopup');
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    closeAdPopup();
                }
            });
        });
    </script>

</body>

</html>