<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// ประมวลผลฟอร์มเมื่อมีการ POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $title = $_POST['title'];
    $description = $_POST['description'];
    $interests = $_POST['interests'];
    $activity_date = $_POST['activity_date'];
    $activity_time = $_POST['activity_time'];
    $post_local = $_POST['post_local'];
    $max_members = $_POST['max_members'];
    $user_id = $_SESSION['user_id'];

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // แทรกข้อมูลโพสต์ลงในตาราง community_posts
        $sql = "INSERT INTO community_posts (
                    title, description, user_id, activity_date, activity_time, 
                    created_at, updated_at, post_local, max_members, current_members, activity_image
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    NOW(), NOW(), ?, ?, 1, 'default.jpg'
                )";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssisssi',
            $title,
            $description,
            $user_id,
            $activity_date,
            $activity_time,
            $post_local,
            $max_members
        );
        $stmt->execute();

        // รับ post_id ของโพสต์ที่เพิ่งถูกแทรก
        $post_id = $conn->insert_id;

        // เพิ่มผู้สร้างเป็นผู้เข้าร่วมอัตโนมัติ
        $join_sql = "INSERT INTO post_members (post_id, user_id, status, joined_at) 
                     VALUES (?, ?, 'confirmed', NOW())";
        $join_stmt = $conn->prepare($join_sql);
        $join_stmt->bind_param("ii", $post_id, $user_id);
        $join_stmt->execute();

        // แทรกข้อมูลกิจกรรมที่เลือก
        if (isset($_POST['interests'])) {
            foreach ($_POST['interests'] as $interest_id) {
                $interest_sql = "INSERT INTO post_interests (post_id, interest_id) VALUES (?, ?)";
                $interest_stmt = $conn->prepare($interest_sql);
                $interest_stmt->bind_param('ii', $post_id, $interest_id);
                $interest_stmt->execute();
            }
        }

        // ยืนยัน transaction
        $conn->commit();

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'โพสต์และกิจกรรมถูกสร้างเรียบร้อยแล้ว!'
        ];
        
        // เปลี่ยนเส้นทางไปยังหน้ารายละเอียดกิจกรรม
        header("Location: post_detail.php?post_id=" . $post_id);
        exit();
    } catch (Exception $e) {
        // ถ้าเกิดข้อผิดพลาด ให้ rollback การทำงานทั้งหมด
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'เกิดข้อผิดพลาดในการสร้างกิจกรรม: ' . $e->getMessage()
        ];
    }
}

// ดึงรายการความสนใจทั้งหมด
$sql = "SELECT * FROM interests ORDER BY interest_name";
$result = $conn->query($sql);
$interests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo - สร้างกิจกรรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html , body{
            overflow: hidden;
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
            margin: 0;
        }

        .interest-tag:hover {
            background-color: #e9ecef;
        }

        .interest-tag.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-create {
            padding: 0.5rem 2rem;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-create:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <div class="row">
        <div class="col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>
        <div class="col-md-10">
            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show">
                    <?php
                    echo $_SESSION['alert']['message'];
                    unset($_SESSION['alert']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-10 mt-5">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4 text-center">
                                <i class="fas fa-plus-circle me-2" style="color: #0d6efd;"></i>สร้างกิจกรรมใหม่
                            </h3>

                            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
                                <!-- หัวข้อและรายละเอียด -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">หัวข้อกิจกรรม</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        placeholder="ใส่หัวข้อกิจกรรมของคุณ" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">รายละเอียดกิจกรรม</label>
                                    <textarea class="form-control" id="description" name="description"
                                        rows="4" placeholder="อธิบายรายละเอียดกิจกรรม" required></textarea>
                                </div>

                                <!-- สถานที่ -->
                                <div class="mb-3">
                                    <label for="post_local" class="form-label">สถานที่จัดกิจกรรม</label>
                                    <input type="text" class="form-control" id="post_local" name="post_local"
                                        placeholder="กรุณากรอกชื่อสถานที่" required>
                                </div>

                                <!-- จำนวนคน วันที่ และเวลา -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="max_members" class="form-label">จำนวนผู้เข้าร่วมสูงสุด</label>
                                        <input type="number" class="form-control" id="max_members" name="max_members"
                                            min="3" max="20" value="3" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="activity_date" class="form-label">วันที่จัดกิจกรรม</label>
                                        <input type="date" class="form-control" id="activity_date" name="activity_date"
                                            min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="activity_time" class="form-label">เวลาเริ่มกิจกรรม</label>
                                        <input type="time" class="form-control" id="activity_time" name="activity_time" required>
                                    </div>
                                </div>

                                <!-- ความสนใจ -->
                                <div class="mb-4">
                                    <label class="form-label">ความสนใจที่เกี่ยวข้อง (เลือกได้ไม่เกิน 5 อย่าง)</label>
                                    <div class="interest-tags-container mb-2">
                                        <?php foreach ($interests as $interest): ?>
                                            <label class="interest-tag">
                                                <input type="checkbox" name="interests[]"
                                                    value="<?php echo $interest['id']; ?>"
                                                    style="display:none">
                                                <?php echo htmlspecialchars($interest['interest_name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-danger" id="interest-limit-warning" style="display: none;">
                                        คุณสามารถเลือกได้ไม่เกิน 5 ความสนใจเท่านั้น
                                    </small>
                                </div>

                                <!-- ปุ่มดำเนินการ -->
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-create">
                                        <i class="fas fa-paper-plane me-2"></i>สร้างกิจกรรม
                                    </button>
                                    <a href="index.php" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times me-2"></i>ยกเลิก
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-2 col-md-2 mt-3"><?php displayAd(); ?> <br><?php displayAd(); ?></div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.interest-tag').click(function(e) {
                e.preventDefault();
                const checkbox = $(this).find('input[type="checkbox"]');
                const isChecked = checkbox.prop('checked');
                const selectedCount = $('input[name="interests[]"]:checked').length;

                if (!isChecked && selectedCount >= 5) {
                    $('#interest-limit-warning').show();
                    return;
                }

                $(this).toggleClass('active');
                checkbox.prop('checked', !isChecked);

                if (selectedCount < 5) {
                    $('#interest-limit-warning').hide();
                }
            });
        });
    </script>
</body>

</html>