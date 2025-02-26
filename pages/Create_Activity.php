<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// เพิ่มโค้ดประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $destination = $_POST['destination'];
    $travel_date = $_POST['travel_date'];
    $max_members = $_POST['max_members'];
    $selected_interests = isset($_POST['interests']) ? $_POST['interests'] : [];

    $conn->begin_transaction();

    try {
        // สร้างโพสต์
        $sql = "INSERT INTO community_posts (
                        user_id, 
                        title, 
                        description, 
                        destination, 
                        travel_date, 
                        activity_date,
                        activity_time,
                        max_members
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssssi",
            $user_id,
            $title,
            $description,
            $destination,
            $travel_date,
            $_POST['activity_date'],
            $_POST['activity_time'],
            $max_members
        );

        if ($stmt->execute()) {
            $post_id = $conn->insert_id;

            // เพิ่มผู้สร้างเป็นสมาชิกคนแรก
            $member_sql = "INSERT INTO post_members (post_id, user_id) VALUES (?, ?)";
            $member_stmt = $conn->prepare($member_sql);
            $member_stmt->bind_param("ii", $post_id, $user_id);
            $member_stmt->execute();

            // เพิ่มความสนใจของโพสต์
            if (!empty($selected_interests)) {
                $interest_sql = "INSERT INTO post_interests (post_id, interest_id) VALUES (?, ?)";
                $interest_stmt = $conn->prepare($interest_sql);
                foreach ($selected_interests as $interest_id) {
                    $interest_stmt->bind_param("ii", $post_id, $interest_id);
                    $interest_stmt->execute();
                }
            }

            // สร้างกลุ่มแชทสำหรับโพสต์
            $chat_group_sql = "INSERT INTO chat_groups (post_id, name) VALUES (?, ?)";
            $chat_group_stmt = $conn->prepare($chat_group_sql);
            $group_name = "กลุ่มแชท: " . $title;
            $chat_group_stmt->bind_param("is", $post_id, $group_name);

            if ($chat_group_stmt->execute()) {
                $group_id = $conn->insert_id;

                // เพิ่มผู้สร้างโพสต์เป็นสมาชิกกลุ่มแชท
                $member_sql = "INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)";
                $member_stmt = $conn->prepare($member_sql);
                $member_stmt->bind_param("ii", $group_id, $user_id);
                $member_stmt->execute();
            }

            $conn->commit();
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'สร้างโพสต์สำเร็จ'
            ];
            header("Location: index.php?success=1");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
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
    require_once '../includes/header.php';
    ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4 text-center">
                            <i class="fas fa-plus-circle me-2"></i>สร้างโพสต์ใหม่
                        </h3>

                        <form method="POST" action="process_activity.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">

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

                            <div class="mb-3">
                                <label for="destination" class="form-label">สถานที่</label>
                                <input type="text" class="form-control" id="destination" name="destination"
                                    placeholder="ระบุสถานที่จัดกิจกรรม" required>
                            </div>

                            <div class="mb-3">
                                <label for="travel_date" class="form-label">วันที่เดินทาง</label>
                                <input type="date" class="form-control" id="travel_date" name="travel_date"
                                    min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="max_members" class="form-label">จำนวนคนสูงสุด</label>
                                <input type="number" class="form-control" id="max_members" name="max_members"
                                    min="2" max="20" value="2" required>
                            </div>

                            <div class="mb-3">
                                <label for="activity_date" class="form-label">วันที่จัดกิจกรรม</label>
                                <input type="date" class="form-control" id="activity_date" name="activity_date" required>
                            </div>

                            <div class="mb-3">
                                <label for="activity_time" class="form-label">เวลาเริ่มกิจกรรม</label>
                                <input type="time" class="form-control" id="activity_time" name="activity_time" required>
                            </div>

                            <div class="mb-3">
                                <label for="activity_image" class="form-label">รูปภาพQR Code เข้ากลุ่ม LINE</label>
                                <input type="file" class="form-control" id="activity_image" name="activity_image" accept="image/*">
                                <small class="text-muted">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 5MB</small>
                            </div>

                            <div class="mb-4">
                                <label for="interests" class="form-label">กิจกรรม (เลือกได้หลายอย่าง)</label>
                                <div class="interest-tags-container mb-2">
                                    <?php while ($interest = $interests->fetch_assoc()): ?>
                                        <label class="interest-tag">
                                            <input type="checkbox"
                                                name="interests[]"
                                                value="<?php echo $interest['id']; ?>"
                                                style="display: none;">
                                            <?php echo htmlspecialchars($interest['interest_name']); ?>
                                        </label>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-create w-100">
                                <i class="fas fa-paper-plane me-2"></i>สร้างโพสต์
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // กำหนดค่าวันที่ต่ำสุดเป็นวันนี้
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('travel_date').min = today;

            // จัดการการคลิกที่ interest tag
            $(document).on('click', '.interest-tag', function(e) {
                e.preventDefault();
                $(this).toggleClass('active');
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
            });
        });
    </script>
</body>

</html>