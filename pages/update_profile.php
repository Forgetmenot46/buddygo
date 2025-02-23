<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    
    try {
        $conn->begin_transaction();

        // อัพเดตข้อมูลพื้นฐาน
        if (!empty($firstname) || !empty($lastname)) {
            $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ? WHERE id = ?");
            $stmt->bind_param("ssi", $firstname, $lastname, $user_id);
            $stmt->execute();
        }

        // ดึงข้อมูลรูปปัจจุบัน
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $current_profile = $stmt->get_result()->fetch_assoc();

        // จัดการรูปโปรไฟล์
        if (isset($_POST['selected_avatar']) && !empty($_POST['selected_avatar'])) {
            // กรณีเลือกรูปเริ่มต้น
            $avatar_number = (int)$_POST['selected_avatar'];
            if ($avatar_number >= 1 && $avatar_number <= 3) {
                $new_profile = 'default' . $avatar_number . '.png';
                
                // ลบรูปเก่าถ้าเป็นรูปที่อัพโหลด
                if ($current_profile && $current_profile['profile_picture'] 
                    && strpos($current_profile['profile_picture'], 'default') !== 0) {
                    $old_file = "../uploads/profile_pictures/" . $current_profile['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
        } elseif (!empty($_FILES['new_profile_picture']['name'])) {
            // กรณีอัพโหลดรูปใหม่
            $upload_dir = "../uploads/profile_pictures/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['new_profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("รองรับไฟล์ภาพเท่านั้น (jpg, jpeg, png, gif)");
            }

            $new_profile = uniqid() . '_' . $user_id . '.' . $file_extension;
            $upload_path = $upload_dir . $new_profile;

            // ลบรูปเก่า
            if ($current_profile && $current_profile['profile_picture'] 
                && strpos($current_profile['profile_picture'], 'default') !== 0) {
                $old_file = $upload_dir . $current_profile['profile_picture'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }

            if (!move_uploaded_file($_FILES['new_profile_picture']['tmp_name'], $upload_path)) {
                throw new Exception("ไม่สามารถอัพโหลดรูปได้");
            }
        }

        // อัพเดตรูปโปรไฟล์ในฐานข้อมูล
        if (isset($new_profile)) {
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $new_profile, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("ไม่สามารถอัพเดตรูปโปรไฟล์ได้");
            }
        }

        // อัพเดต interests
        if (isset($_POST['interests'])) {
            $delete_stmt = $conn->prepare("DELETE FROM user_interests WHERE user_id = ?");
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            
            if (!empty($_POST['interests'])) {
                $insert_stmt = $conn->prepare("INSERT INTO user_interests (user_id, interest_id) VALUES (?, ?)");
                foreach ($_POST['interests'] as $interest_id) {
                    $insert_stmt->bind_param("ii", $user_id, $interest_id);
                    $insert_stmt->execute();
                }
            }
        }

        $conn->commit();
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'อัพเดตข้อมูลสำเร็จ'
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in profile update: " . $e->getMessage());
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
    
    header("Location: profile.php");
    exit();
}
?>