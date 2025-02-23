<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo - หน้าแรก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .post-card {
            transition: transform 0.2s;
            height: 100%;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .interest-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            margin: 0.25rem;
            background-color: #e9ecef;
            border-radius: 20px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .matched-interest {
            background-color: #0d6efd;
            color: white;
        }
        .profile-image {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .members-count {
            font-size: 0.875rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .destination-tag {
            color: #dc3545;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .date-tag {
            color: #0d6efd;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-join {
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-join:hover {
            transform: scale(1.05);
        }
        .username {
            font-weight: 600;
            color: #2c3e50;
        }
        .username-tag {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .activity-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
        .activity-details i {
            width: 20px;
            text-align: center;
        }
        .activity-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .description-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .description-section h6 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-footer-section {
            padding: 1rem;
            border-top: 1px solid rgba(0,0,0,0.125);
        }
        .card-header {
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
        }
        @media (max-width: 768px) {
            .col-md-3 {
                margin-bottom: 1rem;
            }
        }
        .interest-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .interest-tag {
            display: inline-block;
            padding: 6px 12px;
            background-color: #e9ecef;
            color: #495057;
            border-radius: 20px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .interest-tag:hover {
            background-color: #4A90E2;
            color: white;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .interest-tags {
                justify-content: center;
            }
            
            .interest-tag {
                margin-bottom: 0.5rem;
            }
        }
        .post-interests {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
        }
        .interest-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .interest-tag {
            display: inline-block;
            padding: 6px 12px;
            background-color: #e9ecef;
            color: #495057;
            border-radius: 20px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .interest-tag:hover {
            background-color: #4A90E2;
            color: white;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .interest-tags {
                justify-content: flex-start;
            }
            
            .interest-tag {
                margin-bottom: 0.5rem;
            }
        }
        .selected-activities {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .interest-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .interest-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background-color: #e9ecef;
            color: #495057;
            border-radius: 20px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .interest-tag i {
            font-size: 0.75rem;
            color: #4A90E2;
        }
        .interest-tag:hover {
            background-color: #4A90E2;
            color: white;
            transform: translateY(-2px);
        }
        .interest-tag:hover i {
            color: white;
        }
        @media (max-width: 768px) {
            .interest-tags {
                justify-content: flex-start;
            }
            
            .interest-tag {
                margin-bottom: 0.5rem;
            }
        }
        .dropdown-toggle::after {
            display: none;
        }
        .btn-link {
            padding: 0.25rem 0.5rem;
            color: #6c757d;
        }
        .btn-link:hover {
            color: #000;
            background-color: rgba(0,0,0,0.05);
            border-radius: 4px;
        }
        .dropdown-menu {
            min-width: 160px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .btn-edit-menu {
            position: absolute;
            top: 0.5-0rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }
        .btn-edit-menu:hover {
            background: #f8f9fa;
            transform: scale(1.1);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        .btn-edit-menu i {
            color: #6c757d;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-edit-menu:hover i {
            color: #0d6efd;
        }
        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.08);
            padding: 0.5rem;
        }
        .dropdown-item {
            border-radius: 0.3rem;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1.2rem;
            text-align: center;
        }
        .dropdown-item.edit {
            color: #0d6efd;
        }
        .dropdown-item.delete {
            color: #dc3545;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .dropdown-item.edit:hover {
            background-color: #e7f1ff;
        }
        .dropdown-item.delete:hover {
            background-color: #fbe7e9;
        }
        .post-status {
            margin-left: auto;
        }
        .badge {
            font-size: 0.875rem;
            padding: 0.5em 1em;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
        }
        .interests-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .interest-tag {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .interest-tag i {
            color: #6c757d;
            font-size: 0.75rem;
        }
        .members-count {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .members-count i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';
    require_once '../includes/header.php';

    // ตรวจสอบการล็อกอิน
    if (!isLoggedIn()) {
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
    $posts_sql = "SELECT p.*, 
            u.username,
            u.profile_picture,
            GROUP_CONCAT(DISTINCT i.interest_name) as interests,
            GROUP_CONCAT(DISTINCT i.id) as interest_ids,
        (SELECT COUNT(*) FROM post_members 
         WHERE post_id = p.post_id 
         AND status = 'joined') as current_members,
        pm.status as member_status,
        GROUP_CONCAT(DISTINCT i.interest_name ORDER BY i.interest_name) as activity_interests
        FROM community_posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN post_interests pi ON p.post_id = pi.post_id
        LEFT JOIN interests i ON pi.interest_id = i.id
    LEFT JOIN post_members pm ON p.post_id = pm.post_id AND pm.user_id = ?
    WHERE p.status = 'active' 
    AND p.activity_date >= CURDATE()
        GROUP BY p.post_id
    ORDER BY p.created_at DESC";

    $posts_stmt = $conn->prepare($posts_sql);
    $posts_stmt->bind_param("i", $_SESSION['user_id']);
    $posts_stmt->execute();
    $posts_result = $posts_stmt->get_result();

    ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- คอลัมน์ซ้าย (กิจกรรมที่เข้าร่วม) -->
            <div class="col-md-3">
                <!-- มีเฉพาะส่วนกิจกรรมที่เข้าร่วม -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-check me-2"></i>กิจกรรมที่เข้าร่วม
                    </div>
                    <div class="card-body">
                        <!-- แสดงกิจกรรมที่เข้าร่วม -->
                        <?php
                        $joined_posts_sql = "SELECT p.title, p.activity_date 
                                           FROM community_posts p 
                                           JOIN post_members pm ON p.post_id = pm.post_id 
                                           WHERE pm.user_id = ? AND pm.status = 'joined' 
                                           ORDER BY p.activity_date ASC 
                                           LIMIT 5";
                        $joined_stmt = $conn->prepare($joined_posts_sql);
                        $joined_stmt->bind_param("i", $user_id);
                        $joined_stmt->execute();
                        $joined_posts = $joined_stmt->get_result();
                        
                        if ($joined_posts->num_rows > 0) {
                            while ($post = $joined_posts->fetch_assoc()) {
                                echo '<div class="mb-2">';
                                echo '<small class="text-muted">' . date('d/m/Y', strtotime($post['activity_date'])) . '</small>';
                                echo '<div>' . htmlspecialchars($post['title']) . '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="text-muted">ยังไม่มีกิจกรรมที่เข้าร่วม</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- คอลัมน์กลาง (โพสต์กิจกรรม) -->
            <div class="col-md-6">
                <!-- ปุ่มสร้างกิจกรรมใหม่ -->
                <div class="mb-4">
                    <a href="Create_Activity.php" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>สร้างกิจกรรมใหม่
                    </a>
                </div>

                <!-- รายการโพสต์ -->
                    <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                        <?php while ($post = $posts_result->fetch_assoc()): ?>
                        <div class="card post-card mb-4">
                                    <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo getProfileImage($post['user_id']); ?>" 
                                             class="rounded-circle me-2" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($post['title']); ?></h5>
                                            <small class="text-muted">โดย <?php echo htmlspecialchars($post['username']); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <?php 
                                                    $post_data = [
                                                        'post_id' => $post['post_id'],
                                                        'title' => $post['title'],
                                                        'description' => $post['description'],
                                                        'activity_date' => $post['activity_date'],
                                                        'activity_time' => $post['activity_time'],
                                                        'max_members' => $post['max_members']
                                                    ];
                                                    ?>
                                                    <a class="dropdown-item" href="#" onclick="editPost(<?php echo htmlspecialchars(json_encode($post_data)); ?>)">
                                                        <i class="fas fa-edit me-2"></i>แก้ไข
                                                    </a>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger" 
                                                            onclick="deletePost(<?php echo $post['post_id']; ?>)">
                                                        <i class="fas fa-trash-alt me-2"></i>ลบ
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h4 class="card-title mb-3">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </h4>
                                
                                <?php if ($post['interest_ids']): ?>
                                        <?php endif; ?>

                                <div class="activity-details mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                        <span><?php echo htmlspecialchars($post['destination']); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <span>วันที่: <?php echo date('d/m/Y', strtotime($post['activity_date'])); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <span>เวลา: <?php echo date('H:i', strtotime($post['activity_time'])); ?> น.</span>
                                    </div>
                                </div>

                                <div class="description-section mb-3">
                                    <h6 class="text-muted mb-2">รายละเอียด</h6>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                                </div>

                                <?php if (!empty($post['activity_interests'])): ?>
                                    <div class="interests-container mt-2">
                                                <?php 
                                        $interests = explode(',', $post['activity_interests']);
                                        foreach ($interests as $interest): 
                                        ?>
                                            <span class="interest-tag">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars(trim($interest)); ?>
                                            </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                <div class="card-footer-section d-flex justify-content-between align-items-center">
                                    <div class="members-count">
                                        <i class="fas fa-users"></i>
                                        <?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?> คน
                                    </div>
                                    
                                    <div class="post-status">
                                        <?php 
                                        switch ($post['member_status']) {
                                            case 'joined':
                                                echo '<span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>คุณเข้าร่วมแล้ว
                                                      </span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>รอการตอบรับ
                                                      </span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge bg-danger">
                                                        <i class="fas fa-times-circle me-1"></i>ถูกปฏิเสธ
                                                      </span>';
                                                break;
                                            default:
                                                if ($post['current_members'] >= $post['max_members']) {
                                                    echo '<span class="badge bg-secondary">
                                                            <i class="fas fa-users-slash me-1"></i>เต็มแล้ว
                                                          </span>';
                                                } else {
                                                    echo '<form action="join_activity.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="post_id" value="' . $post['post_id'] . '">
                                                            <button type="submit" name="action" value="join" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-user-plus me-1"></i>เข้าร่วม
                                                            </button>
                                                          </form>';
                                                }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- เพิ่ม Modal สำหรับแก้ไขโพสต์ -->
                        <div class="modal fade" id="editPostModal<?php echo $post['post_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">แก้ไขโพสต์</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="edit_post.php" method="POST">
                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">หัวข้อกิจกรรม</label>
                                                <input type="text" class="form-control" name="title" 
                                                       value="<?php echo htmlspecialchars($post['title']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <textarea class="form-control" name="description" rows="4" required><?php 
                                                    echo htmlspecialchars($post['description']); 
                                                ?></textarea>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">วันที่จัดกิจกรรม</label>
                                                    <input type="date" class="form-control" name="activity_date" 
                                                           value="<?php echo $post['activity_date']; ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">เวลา</label>
                                                    <input type="time" class="form-control" name="activity_time" 
                                                           value="<?php echo $post['activity_time']; ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">จำนวนคนที่รับ</label>
                                                <input type="number" class="form-control" name="max_members" 
                                                       value="<?php echo $post['max_members']; ?>" required>
                                            </div>

                                            <div class="text-end">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                                            </div>
                                        </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if ($posts_result->num_rows === 0): ?>
                            <div class="alert alert-info">
                                ไม่พบโพสต์ที่คุณสามารถเข้าร่วมได้ในขณะนี้
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
            </div>

            <!-- คอลัมน์ขวา (กิจกรรมยอดนิยม) -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-fire me-2"></i>กิจกรรมยอดนิยม
                    </div>
                    <div class="card-body">
                        <!-- แสดงกิจกรรมยอดนิยม -->
                        <?php
                        $popular_posts_sql = "SELECT p.title, COUNT(pm.user_id) as member_count 
                                            FROM community_posts p 
                                            JOIN post_members pm ON p.post_id = pm.post_id 
                                            WHERE pm.status = 'joined' 
                                            GROUP BY p.post_id 
                                            ORDER BY member_count DESC 
                                            LIMIT 5";
                        $popular_posts = $conn->query($popular_posts_sql);
                        
                        if ($popular_posts->num_rows > 0) {
                            while ($post = $popular_posts->fetch_assoc()) {
                                echo '<div class="mb-2">';
                                echo '<div>' . htmlspecialchars($post['title']) . '</div>';
                                echo '<small class="text-muted">' . $post['member_count'] . ' คนเข้าร่วม</small>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="text-muted">ยังไม่มีกิจกรรมยอดนิยม</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
    function confirmJoin(postId) {
        if (confirm('คุณต้องการเข้าร่วมกิจกรรมนี้ใช่หรือไม่?')) {
            // สร้าง form สำหรับส่งข้อมูล
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'join_activity.php';

            // เพิ่ม input fields
            const postIdInput = document.createElement('input');
            postIdInput.type = 'hidden';
            postIdInput.name = 'post_id';
            postIdInput.value = postId;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'join';

            // เพิ่ม input fields เข้าไปใน form
            form.appendChild(postIdInput);
            form.appendChild(actionInput);

            // เพิ่ม form เข้าไปใน document และ submit
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deletePost(postId) {
        if (confirm('คุณต้องการลบโพสต์นี้ใช่หรือไม่?')) {
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
                    alert('เกิดข้อผิดพลาดในการลบโพสต์');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการลบโพสต์');
            });
        }
    }

    function editPost(postData) {
        // Convert string to object if needed
        if (typeof postData === 'string') {
            postData = JSON.parse(postData);
        }
        
        // Fill the modal with post data
        document.getElementById('edit_post_id').value = postData.post_id;
        document.getElementById('edit_title').value = postData.title;
        document.getElementById('edit_description').value = postData.description;
        document.getElementById('edit_activity_date').value = postData.activity_date;
        document.getElementById('edit_activity_time').value = postData.activity_time;
        document.getElementById('edit_max_members').value = postData.max_members;

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('editPostModal'));
        modal.show();
    }

    function updatePost() {
        const formData = new FormData(document.getElementById('editPostForm'));

        fetch('update_post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาดในการแก้ไขโพสต์');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการแก้ไขโพสต์');
        });
    }
    </script>

    <!-- ตรวจสอบว่ามีการโหลด Bootstrap JS -->
    <?php if (!isset($bootstrap_js_loaded)): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <?php $bootstrap_js_loaded = true; ?>
    <?php endif; ?>

    <!-- Add this modal HTML before the closing </body> tag -->
    <div class="modal fade" id="editPostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขโพสต์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPostForm">
                        <input type="hidden" id="edit_post_id" name="post_id">
                        
                        <div class="mb-3">
                            <label class="form-label">หัวข้อกิจกรรม</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">วันที่จัดกิจกรรม</label>
                                <input type="date" class="form-control" id="edit_activity_date" name="activity_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เวลา</label>
                                <input type="time" class="form-control" id="edit_activity_time" name="activity_time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">จำนวนคนที่รับ</label>
                            <input type="number" class="form-control" id="edit_max_members" name="max_members" required>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="button" class="btn btn-primary" onclick="updatePost()">บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>