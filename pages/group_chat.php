<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all active groups (activities)
$groups_sql = "SELECT p.post_id, p.title, p.activity_date, p.activity_time, p.post_local, 
               p.max_members, p.current_members, u.username as creator_name, u.profile_picture,
               (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id) as member_count,
               (SELECT COUNT(*) FROM chat_messages cm 
                JOIN chat_groups cg ON cm.group_id = cg.group_id 
                WHERE cg.post_id = p.post_id) as message_count,
               (SELECT status FROM post_members WHERE post_id = p.post_id AND user_id = ?) as user_status
               FROM community_posts p
               JOIN users u ON p.user_id = u.id
               JOIN post_members pm ON p.post_id = pm.post_id
               WHERE p.status = 'active' 
               AND pm.user_id = ? 
               AND (pm.status = 'confirmed' OR pm.status = 'interested')
               GROUP BY p.post_id
               ORDER BY p.created_at DESC";
$groups_stmt = $conn->prepare($groups_sql);
$groups_stmt->bind_param("ii", $user_id, $user_id);
$groups_stmt->execute();
$groups_result = $groups_stmt->get_result();

// Handle if a specific group is selected
$selected_group = null;
$group_members = [];
$messages = [];

if (isset($_GET['group_id'])) {
    $group_id = $_GET['group_id'];
    
    // Check if user is a member of this group
    $member_check_sql = "SELECT * FROM post_members WHERE post_id = ? AND user_id = ?";
    $member_check_stmt = $conn->prepare($member_check_sql);
    $member_check_stmt->bind_param("ii", $group_id, $user_id);
    $member_check_stmt->execute();
    $is_member = $member_check_stmt->get_result()->num_rows > 0;
    
    // If not a member, add them automatically
    if (!$is_member) {
        $join_sql = "INSERT INTO post_members (post_id, user_id, status, joined_at) 
                     VALUES (?, ?, 'interested', NOW())";
        $join_stmt = $conn->prepare($join_sql);
        $join_stmt->bind_param("ii", $group_id, $user_id);
        $join_stmt->execute();
    }
    
    // Get group details
    $group_sql = "SELECT p.*, u.username as creator_name, u.profile_picture 
                 FROM community_posts p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.post_id = ?";
    $group_stmt = $conn->prepare($group_sql);
    $group_stmt->bind_param("i", $group_id);
    $group_stmt->execute();
    $selected_group = $group_stmt->get_result()->fetch_assoc();
    
    // Get group members
    $members_sql = "SELECT u.id, u.username, u.profile_picture, pm.status, pm.joined_at
                   FROM post_members pm
                   JOIN users u ON pm.user_id = u.id
                   WHERE pm.post_id = ?
                   ORDER BY pm.joined_at ASC";
    $members_stmt = $conn->prepare($members_sql);
    $members_stmt->bind_param("i", $group_id);
    $members_stmt->execute();
    $group_members = $members_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Check if chat_messages table exists, if not create it
    $table_check_sql = "SHOW TABLES LIKE 'chat_messages'";
    $table_exists = $conn->query($table_check_sql)->num_rows > 0;
    
    if (!$table_exists) {
        $create_table_sql = "CREATE TABLE chat_messages (
            message_id INT(11) NOT NULL AUTO_INCREMENT,
            group_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            image TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id),
            FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_table_sql);
    } else {
        // Check if image column exists
        $column_check_sql = "SHOW COLUMNS FROM chat_messages LIKE 'image'";
        $column_exists = $conn->query($column_check_sql)->num_rows > 0;
        
        if (!$column_exists) {
            // Add image column if it doesn't exist
            $add_column_sql = "ALTER TABLE chat_messages ADD COLUMN image TEXT AFTER message";
            $conn->query($add_column_sql);
        }
    }
    
    // Get messages
    $messages_sql = "SELECT m.*, u.username, u.profile_picture 
                    FROM chat_messages m
                    JOIN users u ON m.user_id = u.id
                    JOIN chat_groups cg ON m.group_id = cg.group_id
                    WHERE cg.post_id = ?
                    ORDER BY m.created_at ASC";
    $messages_stmt = $conn->prepare($messages_sql);
    $messages_stmt->bind_param("i", $group_id);
    $messages_stmt->execute();
    $messages = $messages_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $post_id = $_POST['group_id'];
    $message = trim($_POST['message']);
    $image = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $upload_dir = '../uploads/chat_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'uploads/chat_images/' . $new_filename;
            }
        }
    }
    
    if (!empty($message) || $image !== null) {
        // Get or create chat group
        $get_group_sql = "SELECT group_id FROM chat_groups WHERE post_id = ?";
        $get_group_stmt = $conn->prepare($get_group_sql);
        $get_group_stmt->bind_param("i", $post_id);
        $get_group_stmt->execute();
        $group_result = $get_group_stmt->get_result();
        
        if ($group_result->num_rows == 0) {
            // Create new chat group
            $create_group_sql = "INSERT INTO chat_groups (post_id) VALUES (?)";
            $create_group_stmt = $conn->prepare($create_group_sql);
            $create_group_stmt->bind_param("i", $post_id);
            $create_group_stmt->execute();
            $group_id = $conn->insert_id;
        } else {
            $group_id = $group_result->fetch_assoc()['group_id'];
        }
        
        // Insert message
        $insert_sql = "INSERT INTO chat_messages (group_id, user_id, message, image) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiss", $group_id, $user_id, $message, $image);
        
        if ($insert_stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: group_chat.php?group_id=" . $post_id);
            exit();
        }
    }
}

// Handle group actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $group_id = $_GET['group_id'];
    
    if ($action === 'leave' && isset($group_id)) {
        $leave_sql = "DELETE FROM post_members WHERE post_id = ? AND user_id = ?";
        $leave_stmt = $conn->prepare($leave_sql);
        $leave_stmt->bind_param("ii", $group_id, $user_id);
        
        if ($leave_stmt->execute()) {
            header("Location: group_chat.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuddyGo - กลุ่มแชท</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/profilestyle.css" rel="stylesheet">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
            position: relative;
        }
        
        .message-mine {
            background-color: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .message-others {
            background-color: #ffffff;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .message-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .message-username {
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-left: 10px;
        }
        
        .message-content {
            word-wrap: break-word;
        }
        
        .chat-input {
            padding: 15px;
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
        }
        
        .group-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .group-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .group-item:hover {
            background-color: #f8f9fa;
        }
        
        .group-item.active {
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
        }
        
        .group-info {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .members-list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-2">
            <?php require_once '../includes/header.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-12 col-md-10 mt-4">
            <div class="container-fluid">
                <div class="row">
                    <?php if (!isset($_GET['group_id'])): ?>
                        <!-- Welcome screen when no group is selected -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                    <h4>ยินดีต้อนรับสู่ระบบแชท</h4>
                                    <p class="text-muted">กรุณาเข้าร่วมกิจกรรมที่สนใจเพื่อเริ่มการสนทนา</p>
                                    <a href="index.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-search me-2"></i>ค้นหากิจกรรมที่น่าสนใจ
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        // Check if user is a member (either interested or confirmed)
                        $check_member_sql = "SELECT status FROM post_members WHERE post_id = ? AND user_id = ? AND (status = 'confirmed' OR status = 'interested')";
                        $check_member_stmt = $conn->prepare($check_member_sql);
                        $check_member_stmt->bind_param("ii", $_GET['group_id'], $user_id);
                        $check_member_stmt->execute();
                        $is_member = $check_member_stmt->get_result()->num_rows > 0;

                        if (!$is_member):
                        ?>
                            <!-- Show message if user is not a member -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body text-center p-5">
                                        <i class="fas fa-lock fa-4x text-warning mb-3"></i>
                                        <h4>คุณยังไม่สามารถเข้าถึงการสนทนาได้</h4>
                                        <p class="text-muted">กรุณาแสดงความสนใจเข้าร่วมกิจกรรมก่อนเข้าร่วมการสนทนา</p>
                                        <a href="post_detail.php?post_id=<?php echo $_GET['group_id']; ?>" class="btn btn-primary mt-3">
                                            <i class="fas fa-sign-in-alt me-2"></i>แสดงความสนใจเข้าร่วมกิจกรรม
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Chat Area -->
                            <div class="row">
                                <!-- Chat Section -->
                                <div class="col-md-8">
                                    <?php if ($selected_group): ?>
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($selected_group['title']); ?></h5>
                                                    <div>
                                                        <a href="post_detail.php?post_id=<?php echo $selected_group['post_id']; ?>" class="btn btn-sm btn-light">
                                                            <i class="fas fa-info-circle"></i> รายละเอียดกิจกรรม
                                                        </a>
                                                        <a href="group_chat.php?action=leave&group_id=<?php echo $selected_group['post_id']; ?>" 
                                                           class="btn btn-sm btn-danger ms-2"
                                                           onclick="return confirm('คุณแน่ใจหรือไม่ที่จะออกจากกลุ่มนี้?');">
                                                            <i class="fas fa-sign-out-alt"></i> ออกจากกลุ่ม
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="chat-container">
                                                    <div class="chat-messages" id="chatMessages">
                                                        <?php if (count($messages) > 0): ?>
                                                            <?php foreach ($messages as $message): ?>
                                                                <div class="message <?php echo ($message['user_id'] == $user_id) ? 'message-mine' : 'message-others'; ?>">
                                                                    <?php if ($message['user_id'] != $user_id): ?>
                                                                        <div class="message-header">
                                                                            <img src="<?php echo getProfileImage($message['user_id']); ?>" class="message-avatar">
                                                                            <span class="message-username"><?php echo htmlspecialchars($message['username']); ?></span>
                                                                            <span class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="message-content">
                                                                        <?php if (!empty($message['message'])): ?>
                                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($message['image'])): ?>
                                                                            <div class="message-image">
                                                                                <img src="../<?php echo htmlspecialchars($message['image']); ?>" 
                                                                                    class="img-fluid rounded cursor-pointer" 
                                                                                    style="max-width: 200px; cursor: pointer;"
                                                                                    onclick="showImageModal(this.src)"
                                                                                    alt="Chat image">
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if ($message['user_id'] == $user_id): ?>
                                                                        <div class="text-end">
                                                                            <span class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="text-center p-4">
                                                                <p class="text-muted">หากมีการรายงานการผิดนัด ระบบจำกัดการเข้าร่วมกิจกรรมเป็นเวลา 3 วัน ถ้าทำผิดครั้งที่2 จำกัดเป็นเวลา7วัน หากมีครั้งที่3 จะทำการลบแอคเคาท์ออกจากระบบ </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="chat-input">
                                                        <form method="POST" action="group_chat.php?group_id=<?php echo $selected_group['post_id']; ?>" enctype="multipart/form-data">
                                                            <input type="hidden" name="group_id" value="<?php echo $selected_group['post_id']; ?>">
                                                            <div class="input-group">
                                                                <textarea class="form-control" name="message" placeholder="พิมพ์ข้อความ..." rows="1"></textarea>
                                                                <label class="btn btn-outline-secondary" for="image-upload">
                                                                    <i class="fas fa-image"></i>
                                                                    <input type="file" id="image-upload" name="image" accept="image/*" style="display: none;">
                                                                </label>
                                                                <button type="submit" name="send_message" class="btn btn-primary">
                                                                    <i class="fas fa-paper-plane"></i>
                                                                </button>
                                                            </div>
                                                            <small class="text-muted" id="selected-image"></small>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="card">
                                            <div class="card-body text-center p-5">
                                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                                <h4>เลือกกลุ่มกิจกรรมเพื่อเริ่มการสนทนา</h4>
                                                <p class="text-muted">เลือกกลุ่มจากรายการด้านซ้ายเพื่อเข้าร่วมการสนทนา</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Members and Details Sidebar -->
                                <div class="col-md-4">
                                    <?php if ($selected_group): ?>
                                        <!-- Activity Details -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> รายละเอียดกิจกรรม</h6>
                                            </div>
                                            <div class="card-body">
                                                <p><i class="fas fa-map-marker-alt text-danger"></i> สถานที่: <?php echo htmlspecialchars($selected_group['post_local']); ?></p>
                                                <p><i class="fas fa-calendar text-primary"></i> วันที่: <?php echo htmlspecialchars($selected_group['activity_date']); ?></p>
                                                <p><i class="fas fa-clock text-success"></i> เวลา: <?php echo htmlspecialchars($selected_group['activity_time']); ?></p>
                                                <p><i class="fas fa-users text-info"></i> จำนวนผู้เข้าร่วม: <?php echo $selected_group['current_members']; ?>/<?php echo $selected_group['max_members']; ?></p>
                                            </div>
                                        </div>

                                        <!-- Members List -->
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-users"></i> สมาชิกในกลุ่ม</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($group_members as $member): ?>
                                                        <li class="list-group-item">
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo getProfileImage($member['id']); ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                                <div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($member['username']); ?></div>
                                                                    <small class="text-muted">
                                                                        <?php 
                                                                        if ($member['status'] == 'confirmed') {
                                                                            echo '<span class="text-success">ยืนยันแล้ว</span>';
                                                                        } else {
                                                                            echo '<span class="text-warning">สนใจ</span>';
                                                                        }
                                                                        ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img src="" id="modalImage" class="img-fluid" alt="Enlarged image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll to bottom of chat
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            const imageUpload = document.getElementById('image-upload');
            const selectedImage = document.getElementById('selected-image');
            
            if (imageUpload && selectedImage) {
                imageUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        selectedImage.textContent = 'Selected image: ' + this.files[0].name;
                    } else {
                        selectedImage.textContent = '';
                    }
                });
            }
        });

        // Image Modal Function
        function showImageModal(imageSrc) {
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
    </script>
</body>
</html> 