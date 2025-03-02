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

// Get group ID and message from POST data
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if ($group_id <= 0 || empty($message)) {
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

// Check if group is active
$sql = "SELECT status FROM chat_groups WHERE group_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Group not found']);
    exit();
}

$group = $result->fetch_assoc();
if ($group['status'] !== 'active') {
    echo json_encode(['success' => false, 'error' => 'Group is not active']);
    exit();
}

// Insert message
$sql = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $group_id, $user_id, $message);

if ($stmt->execute()) {
    $message_id = $conn->insert_id;
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message_id' => $message_id,
        'created_at' => date('d/m/Y H:i')
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
} 