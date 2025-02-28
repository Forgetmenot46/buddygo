<?php
session_start();
require_once '../config/config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

// ดึงโพสต์ทั้งหมด โดยเรียงลำดับตามกิจกรรมที่ผู้ใช้สนใจก่อน
$posts_sql = "SELECT p.*, u.username, u.profile_picture, 
        GROUP_CONCAT(DISTINCT i.interest_name) as interests,
        GROUP_CONCAT(DISTINCT i.id) as interest_ids,
        (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'joined') as current_members,
        pm.status as member_status
    FROM community_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN post_interests pi ON p.post_id = pi.post_id
    LEFT JOIN interests i ON pi.interest_id = i.id
    LEFT JOIN post_members pm ON p.post_id = pm.post_id AND pm.user_id = ?
    WHERE p.status = 'active' 
    GROUP BY p.post_id
    ORDER BY p.created_at DESC";

$posts_stmt = $conn->prepare($posts_sql);
$posts_stmt->bind_param("i", $_SESSION['user_id']);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

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
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>

        <!-- คอลัมน์กลาง (โพสต์กิจกรรม) -->
        <div class="col-12 col-md-6 offset-md-1 mt-5"> <!-- เพิ่ม offset-lg-1 เพื่อเว้นระยะจาก sidebar -->
            <div class="d-flex justify-content-between mb-4 mt-3"> <!-- ใช้ d-flex เพื่อให้ในแถวเดียวกัน -->
                <a href="Create_Activity.php" class="btn btn-primary justify-content-center w-100">
                    <i class="fas fa-plus me-2"></i>สร้างกิจกรรมใหม่
                </a>
            </div>

            <!-- รายการโพสต์ -->
            <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <div class="card post-card mb-4" style="cursor: pointer;" onclick="window.location.href='post_detail.php?post_id=<?php echo $post['post_id']; ?>'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <!-- รูปโปรไฟล์ -->
                                    <img src="<?php echo getProfileImage($post['user_id']); ?>"
                                        class="rounded-circle me-2"
                                        style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <h5 class="card-title mb-0">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h5>
                                        <small class="text-muted">โดย <?php echo htmlspecialchars($post['username']); ?></small>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <!-- เมนูการดำเนินการ (ถ้าเป็นผู้โพสต์) -->
                                    <div class="post-actions">
                                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-link text-dark" 
                                                        data-bs-toggle="dropdown" 
                                                        onclick="event.stopPropagation();">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="edit_post.php?post_id=<?php echo $post['post_id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>แก้ไขโพสต์
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="delete_post.php?post_id=<?php echo $post['post_id']; ?>" 
                                                           onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบโพสต์นี้?');">
                                                            <i class="fas fa-trash-alt me-2"></i>ลบโพสต์
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- รายละเอียดโพสต์ -->
                            <p class="card-text"><?php echo htmlspecialchars($post['description']); ?></p>
                            <p class="card-text">
                                <small class="text-muted">วันที่จัดกิจกรรม: <strong><?php echo $post['activity_date']; ?></strong></small>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">สถานที่: <strong><?php echo htmlspecialchars($post['post_local']); ?></strong></small>
                            </p>

                            <!-- แสดงแท็กกิจกรรม -->
                            <p class="card-text">
                                <small class="text-muted">กิจกรรม:
                                    <div class="interest-tags">
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

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
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
    function deletePost(event, postId) {
        event.preventDefault();
        event.stopPropagation();
        
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
                    location.reload();
                } else {
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
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl)
        });
    });
    </script>

    <footer><?php include '../includes/footer.php'; ?></footer>
</body>


</html>