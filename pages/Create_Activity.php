<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// เพิ่มโค้ดประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $title = $_POST['title'];
    $description = $_POST['description'];
    $interests = $_POST['interests'];  // อาร์เรย์ของกิจกรรมที่เลือก
    $destination = $_POST['destination'];
    $activity_date = $_POST['activity_date'];
    $post_local = $_POST['post_local'];

    // แทรกข้อมูลโพสต์ลงในตาราง community_posts
    $sql = "INSERT INTO community_posts (title, description, user_id, destination, activity_date, created_at, updated_at, post_local) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssisss', $title, $description, $user_id, $destination, $activity_date, $post_local);
    $result = $stmt->execute();

    // รับ post_id ของโพสต์ที่เพิ่งถูกแทรก
    $post_id = $conn->insert_id;

    // แทรกข้อมูลกิจกรรมที่เลือกลงในตาราง community_post_interests
    if (isset($_POST['interests'])) {  // ตรวจสอบว่ามีการเลือกความสนใจหรือไม่
        foreach ($_POST['interests'] as $interest_id) {
            $sql = "INSERT INTO community_post_interests (post_id, interest_id) 
                    VALUES (?, ?)";
            $interest_stmt = $conn->prepare($sql);
            $interest_stmt->bind_param('ii', $post_id, $interest_id);
            if (!$interest_stmt->execute()) {
                echo "Error: " . $interest_stmt->error; // แสดงข้อผิดพลาดหากมี
            }
        }
    }

    // เพิ่มข้อความแจ้งเตือนหรือดำเนินการต่อหลังจากบันทึกเสร็จ
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'โพสต์และกิจกรรมถูกสร้างเรียบร้อยแล้ว!'
    ];
    header("Location: community_posts.php");  // เปลี่ยนไปที่หน้าหลังการสร้างโพสต์
    exit();
}

// ดึงรายการความสนใจทั้งหมด
$interests_sql = "SELECT * FROM interests ORDER BY interest_name";
$interests = $conn->query($interests_sql);
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo - สร้างโพสต์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html {
            overflow: auto;
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

        @media (max-width: 767.98px) {
            .interest-tag {
                padding: 8px 16px;
                font-size: 1rem;
            }

            .interest-tags-container {
                gap: 10px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <?php
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';
    ?>

<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['alert']['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- คอลัมน์ซ้าย (Sidebar) -->
        <div class="col-12 col-md-3 col-lg-2">
            <?php require_once '../includes/header.php'; ?>
        </div>
        
        <!-- คอลัมน์หลัก -->
        <div class="col">
            <div class="card mt-4 mb-4">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">
                        <i class="fas fa-plus-circle me-2" style="color: #0d6efd;"></i>สร้างโพสต์ใหม่
                    </h3>
                    
                    <form method="POST" action="process_activity.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <!-- หัวข้อและรายละเอียด -->
                        <div class="mb-3">
                            <label for="title" class="form-label">หัวข้อ</label>
                            <input type="text" class="form-control" id="title" name="title"
                            placeholder="ใส่หัวข้อกิจกรรมของคุณ" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">รายละเอียด</label>
                            <textarea class="form-control" id="description" name="description"
                            rows="4" placeholder="อธิบายรายละเอียดกิจกรรม" required></textarea>
                        </div>
                        
                        <!-- จัดให้สถานที่อยู่คนเดียว -->
                        <div class="mb-3">
                            <label for="post_local" class="form-label">สถานที่</label>
                            <input type="text" class="form-control" id="post_local" name="post_local" placeholder="กรุณากรอกชื่อสถานที่" required>
                        </div>
                        
                        <!-- จัดให้จำนวนคน, วันที่และเวลาจัดกิจกรรมอยู่แถวเดียวกัน -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="max_members" class="form-label">จำนวนคนสูงสุด</label>
                                <input type="number" class="form-control" id="max_members" name="max_members"
                                    min="2" max="20" value="2" required>
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

                        <!-- QR Code -->
                        <div class="mb-3">
                            <label for="activity_image" class="form-label">รูปภาพQR Code เข้ากลุ่ม LINE</label>
                            <input type="file" class="form-control" id="activity_image" name="activity_image" accept="image/*">
                            <small class="text-muted">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 5MB</small>
                        </div>

                        <!-- ส่วนของการเลือกกิจกรรม -->
                        <div class="mb-4">
                            <label for="interests" class="form-label">กิจกรรม (เลือกได้ไม่เกิน 5 อย่าง)</label>
                            <div class="interest-tags-container mb-2">
                                <?php while ($interest = $interests->fetch_assoc()): ?>
                                    <label class="interest-tag">
                                        <input type="checkbox" name="interests[]"
                                            value="<?php echo $interest['id']; ?>"
                                            style="display: none;">
                                        <?php echo htmlspecialchars($interest['interest_name']); ?>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            <small class="text-danger" id="interest-limit-warning" style="display: none;">
                                คุณสามารถเลือกได้ไม่เกิน 5 กิจกรรมเท่านั้น
                            </small>
                        </div>


                        <button type="submit" class="btn btn-primary btn-create w-100">
                            <i class="fas fa-paper-plane me-2"></i>สร้างโพสต์
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-1"></div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // จัดการการคลิกที่ interest tag
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

                // ซ่อนข้อความเตือนถ้าจำนวนที่เลือกน้อยกว่า 5
                if (selectedCount < 5) {
                    $('#interest-limit-warning').hide();
                }
            });
        });
    </script>
</body>
</html>