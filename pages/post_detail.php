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
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user_sql = "SELECT username, profile_picture FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

// เก็บข้อมูลผู้ใช้ไว้ใน session
$_SESSION['username'] = $current_user['username'];
$_SESSION['profile_picture'] = $current_user['profile_picture'];

// ดึงข้อมูลโพสต์และข้อมูลที่เกี่ยวข้องทั้งหมด
$post_sql = "SELECT p.*, u.username, u.profile_picture,
             GROUP_CONCAT(DISTINCT i.interest_name) as interests,
             (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'confirmed') as current_members
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

// เพิ่ม SQL query สำหรับดึงข้อมูลผู้เข้าร่วมทั้งหมด หลังจาก query แรก
$participants_sql = "SELECT u.id, u.username, u.profile_picture, pm.status 
                    FROM post_members pm 
                    JOIN users u ON pm.user_id = u.id 
                    WHERE pm.post_id = ? AND pm.status = 'joined'";
$participants_stmt = $conn->prepare($participants_sql);
$participants_stmt->bind_param("i", $post_id);
$participants_stmt->execute();
$participants_result = $participants_stmt->get_result();

// เพิ่มฟังก์ชันอัพเดทสถิติกิจกรรมยอดนิยม
function updatePopularActivity($post_id, $conn)
{
    $current_month = date('n'); // 1-12
    $current_year = date('Y');

    // ตรวจสอบว่ามีข้อมูลของเดือนนี้หรือไม่
    $check_sql = "SELECT id, join_count FROM popular_activities 
                  WHERE post_id = ? AND month = ? AND year = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $post_id, $current_month, $current_year);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // อัพเดทจำนวนการเข้าร่วม
        $row = $result->fetch_assoc();
        $new_count = $row['join_count'] + 1;
        $update_sql = "UPDATE popular_activities 
                      SET join_count = ? 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_count, $row['id']);
        $update_stmt->execute();
    } else {
        // สร้างข้อมูลใหม่
        $insert_sql = "INSERT INTO popular_activities 
                      (post_id, month, year, join_count) 
                      VALUES (?, ?, ?, 1)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $post_id, $current_month, $current_year);
        $insert_stmt->execute();
    }
}

// เพิ่มฟังก์ชันตรวจสอบสถานะการเข้าร่วม
function checkMemberStatus($post_id, $user_id, $conn)
{
    $sql = "SELECT status FROM post_members WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['status'];
    }
    return null;
}

// เพิ่มฟังก์ชันตรวจสอบว่าสามารถเข้าร่วมได้หรือไม่
function canJoinActivity($post_id, $conn)
{
    $sql = "SELECT current_members, max_members FROM community_posts WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return ($result['current_members'] < $result['max_members']);
}

// ตรวจสอบสถานะการเข้าร่วมของผู้ใช้ปัจจุบัน
$member_status = checkMemberStatus($post_id, $user_id, $conn);
$can_join = canJoinActivity($post_id, $conn);

// แสดงปุ่มตามสถานะการเข้าร่วม
if ($member_status === 'confirmed') {
    $join_button_display = 'none';
    $current_user_display = 'block';
} elseif ($member_status === 'interested') {
    $join_button_display = 'none';
    $confirm_button_display = 'block';
    $current_user_display = 'block';
} else {
    $join_button_display = $can_join ? 'block' : 'none';
    $confirm_button_display = 'none';
    $current_user_display = 'none';
}

// เพิ่มฟังก์ชันตรวจสอบว่าสามารถเข้าร่วมได้หรือไม่
function addParticipant($post_id, $user_id, $conn)
{
    // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
    $check_sql = "SELECT status FROM post_members WHERE post_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // อัพเดทสถานะถ้ามีข้อมูลอยู่แล้ว
        $sql = "UPDATE post_members SET status = 'interested' WHERE post_id = ? AND user_id = ?";
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO post_members (post_id, user_id, status) VALUES (?, ?, 'interested')";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $success = $stmt->execute();

    if ($success) {
        // เพิ่มการอัพเดทสถิติเมื่อมีการเข้าร่วมสำเร็จ
        updatePopularActivity($post_id, $conn);
    }

    return $success;
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
                        <div class="d-flex flex-column gap-2">
                            <a href="index.php" class="btn btn-outline-primary" id="backButton">
                                <i class="fas fa-arrow-left me-2"></i>กลับไปหน้าหลัก
                            </a>
                            <a href="#" class="btn btn-outline-primary" id="joinButton" style="display: <?php echo $join_button_display; ?>">
                                <i class="fa-solid fa-right-to-bracket"></i> เข้าร่วมกิจกรรม
                            </a>

                            <!-- Modal Popup -->
                            <div class="modal" tabindex="-1" id="confirmationModal">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">คำเตือน</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>หากมีการรายงานการผิดนัด ระบบจะจำกัดการเข้าร่วมกิจกรรมเป็นเวลา 3 วัน ถ้าทำผิดครั้งที่2 จำกัดเป็นเวลา7วัน หากมีครั้งที่3 จะทำการลบแอคเคาท์ออกจากระบบ
                                            </p>

                                            <!-- Radio Buttons -->
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="confirmation" id="yesOption" value="yes">
                                                <label class="form-check-label" for="yesOption">
                                                    ยืนยันว่าเข้าใจและพร้อมเข้าร่วมกิจกรรม
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="button" class="btn btn-primary" id="confirmBtn" disabled>ยืนยัน</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- แสดงข้อความถ้าไม่สามารถเข้าร่วมได้ -->
                            <?php if (!$can_join && !$member_status): ?>
                                <div class="alert alert-warning" role="alert">
                                    ขออภัย กิจกรรมนี้มีผู้เข้าร่วมเต็มแล้ว
                                </div>
                            <?php endif; ?>

                            <!-- แสดงสถานะการเข้าร่วมปัจจุบัน -->
                            <?php if ($member_status): ?>
                                <div class="alert alert-info" role="alert">
                                    สถานะของคุณ: <?php echo $member_status === 'confirmed' ? 'เข้าร่วมแล้ว' : 'รอการยืนยัน'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Section to display user's profile -->
            <div id="profileSection" class="card mt-4">
                <div class="card-body">
                    <h5 class="mb-3">ผู้เข้าร่วมกิจกรรม (<?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?>)</h5>

                    <!-- แสดงผู้ที่สนใจเข้าร่วม -->
                    <div class="mb-4">
                        <h6 class="text-warning mb-3">สนใจเข้าร่วมกิจกรรม</h6>
                        <?php
                        $interested_sql = "SELECT u.id, u.username, u.profile_picture, pm.joined_at 
                                         FROM post_members pm 
                                         JOIN users u ON pm.user_id = u.id 
                                         WHERE pm.post_id = ? AND pm.status = 'interested'
                                         ORDER BY pm.joined_at DESC";
                        $interested_stmt = $conn->prepare($interested_sql);
                        $interested_stmt->bind_param("i", $post_id);
                        $interested_stmt->execute();
                        $interested_result = $interested_stmt->get_result();

                        if ($interested_result->num_rows > 0):
                            while ($member = $interested_result->fetch_assoc()):
                        ?>
                                <div class="d-flex align-items-center mb-2 p-2" style="background-color: #fff3cd; border-radius: 8px;">
                                    <img src="<?php echo getProfileImage($member['id']); ?>"
                                        class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($member['username']); ?></h6>
                                        <small class="text-muted">สนใจเข้าร่วม - <?php echo date('d/m/Y H:i', strtotime($member['joined_at'])); ?></small>
                                    </div>
                                    <?php if ($member['id'] == $_SESSION['user_id']): ?>
                                        <div class="ms-auto">
                                            <button class="btn btn-success btn-sm confirm-join-btn"
                                                data-member-id="<?php echo $member['id']; ?>"
                                                onclick="confirmJoin(<?php echo $post_id; ?>, <?php echo $member['id']; ?>)">
                                                <i class="fas fa-check me-1"></i>ยืนยันการเข้าร่วม
                                            </button>
                                            <button class="btn btn-danger btn-sm cancel-join-btn"
                                                data-member-id="<?php echo $member['id']; ?>"
                                                onclick="cancelJoin(<?php echo $post_id; ?>, <?php echo $member['id']; ?>)">
                                                <i class="fas fa-times me-1"></i>ยกเลิก
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                        <?php
                            endwhile;
                        else:
                            echo '<p class="text-muted">ยังไม่มีผู้สนใจเข้าร่วม</p>';
                        endif;
                        ?>
                    </div>

                    <!-- แสดงผู้ที่ยืนยันการเข้าร่วม -->
                    <div>
                        <h6 class="text-success mb-3">ยืนยันการเข้าร่วม</h6>
                        <?php
                        $confirmed_sql = "SELECT u.id, u.username, u.profile_picture, pm.joined_at 
                                        FROM post_members pm 
                                        JOIN users u ON pm.user_id = u.id 
                                        WHERE pm.post_id = ? AND pm.status = 'confirmed'
                                        ORDER BY pm.joined_at DESC";
                        $confirmed_stmt = $conn->prepare($confirmed_sql);
                        $confirmed_stmt->bind_param("i", $post_id);
                        $confirmed_stmt->execute();
                        $confirmed_result = $confirmed_stmt->get_result();

                        if ($confirmed_result->num_rows > 0):
                            while ($member = $confirmed_result->fetch_assoc()):
                        ?>
                                <div class="d-flex align-items-center mb-2 p-2" style="background-color: #d4edda; border-radius: 8px;">
                                    <img src="<?php echo getProfileImage($member['id']); ?>"
                                        class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($member['username']); ?></h6>
                                        <small class="text-muted">ยืนยันเข้าร่วม - <?php echo date('d/m/Y H:i', strtotime($member['joined_at'])); ?></small>
                                    </div>
                                </div>
                        <?php
                            endwhile;
                        else:
                            echo '<p class="text-muted">ยังไม่มีผู้ยืนยันการเข้าร่วม</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // กำหนดตัวแปรที่จำเป็นสำหรับ JavaScript
            const postId = <?php echo $post_id; ?>;
            const userId = <?php echo $_SESSION['user_id']; ?>;
        </script>
        <?php include 'activity_functions.php'; ?>
</body>

</html>