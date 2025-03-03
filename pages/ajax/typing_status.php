<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get group ID and typing status from POST data
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$is_typing = isset($_POST['is_typing']) ? (bool)$_POST['is_typing'] : false;

// Validate input
if ($group_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Check if user is a member of the group
$sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this group']);
    exit();
}

// Check if typing_status table exists
$result = $conn->query("SHOW TABLES LIKE 'typing_status'");
if ($result->num_rows == 0) {
    // Create typing_status table
    $sql = "CREATE TABLE `typing_status` (
        `group_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `is_typing` tinyint(1) NOT NULL DEFAULT 0,
        `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`group_id`,`user_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `typing_status_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`group_id`) ON DELETE CASCADE,
        CONSTRAINT `typing_status_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($sql);
}

// Update typing status
if ($is_typing) {
    // Insert or update typing status
    $sql = "INSERT INTO typing_status (group_id, user_id, is_typing) VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE is_typing = 1, last_updated = CURRENT_TIMESTAMP()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
} else {
    // Update typing status to false
    $sql = "UPDATE typing_status SET is_typing = 0 WHERE group_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
}

echo json_encode(['success' => true]); 