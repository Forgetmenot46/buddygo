<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $oldProfilePicture = $user['profile_picture'];

    // ลบรูปเก่าถ้ามี
    if (isset($_FILES['new_profile_picture']) && $_FILES['new_profile_picture']['error'] == UPLOAD_ERR_OK) {
        if ($oldProfilePicture && $oldProfilePicture !== 'default.png') {
            unlink("../uploads/profile_pictures/" . $oldProfilePicture);
        }

        // อัปโหลดรูปใหม่
        $uploadDir = "../uploads/profile_pictures/";
        $fileExtension = pathinfo($_FILES['new_profile_picture']['name'], PATHINFO_EXTENSION);
        $newFileName = date('YmdHis') . '_' . htmlspecialchars($username) . '.' . $fileExtension; // เปลี่ยนชื่อไฟล์
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['new_profile_picture']['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, firstname = ?, lastname = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $firstname, $lastname, $newFileName, $user_id);
        } else {
            $error = "Failed to upload the new image.";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, firstname = ?, lastname = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $username, $firstname, $lastname, $user_id);
    }

    if (isset($stmt)) {
        $stmt->execute();
        header("Location: profile.php?update=success");
        exit();
    }
}
?>