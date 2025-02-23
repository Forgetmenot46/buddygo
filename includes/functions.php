<?php
// Database connection is already established in config.php

/**
 * Get user information by ID
 */
function getUserById($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Check if user has already joined a post
 */
function hasUserJoinedPost($conn, $post_id, $user_id) {
    $sql = "SELECT * FROM post_members WHERE post_id = ? AND user_id = ? AND status = 'joined'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Get current member count for a post
 */
function getPostMemberCount($conn, $post_id) {
    $sql = "SELECT COUNT(*) as count FROM post_members WHERE post_id = ? AND status = 'joined'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

/**
 * Add comment to a post
 */
function addComment($conn, $post_id, $user_id, $comment) {
    $sql = "INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    return $stmt->execute();
}

/**
 * Get all comments for a post
 */
function getPostComments($conn, $post_id) {
    $sql = "SELECT c.*, u.username 
            FROM post_comments c 
            JOIN users u ON c.user_id = u.user_id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Format date to Thai format
 */
function formatThaiDate($date) {
    $thai_months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thai_months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543; // Convert to Buddhist year
    
    return "$day $month $year";
}

/**
 * Check if post is full
 */
function isPostFull($conn, $post_id) {
    $sql = "SELECT p.max_members, COUNT(pm.user_id) as current_members
            FROM community_posts p
            LEFT JOIN post_members pm ON p.post_id = pm.post_id AND pm.status = 'joined'
            WHERE p.post_id = ?
            GROUP BY p.post_id, p.max_members";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result && $result['current_members'] >= $result['max_members'];
}

/**
 * Get user's joined posts
 */
function getUserJoinedPosts($conn, $user_id) {
    $sql = "SELECT p.*, u.username as creator_name,
            (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'joined') as current_members
            FROM community_posts p
            JOIN users u ON p.user_id = u.user_id
            JOIN post_members pm ON p.post_id = pm.post_id
            WHERE pm.user_id = ? AND pm.status = 'joined'
            ORDER BY p.travel_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get user's created posts
 */
function getUserCreatedPosts($conn, $user_id) {
    $sql = "SELECT p.*, u.username as creator_name,
            (SELECT COUNT(*) FROM post_members WHERE post_id = p.post_id AND status = 'joined') as current_members
            FROM community_posts p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Sanitize and validate input
 */
function sanitizeInput($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

/**
 * Validate date format
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Check if travel date is in the future
 */
function isFutureDate($date) {
    return strtotime($date) > time();
}

// เพิ่มฟังก์ชันสำหรับดึงรูปโปรไฟล์
function getProfileImage($user_id) {
    global $conn;
    
    // กำหนด path ของรูปเริ่มต้น
    $default_image = '../assets/images/default_profiles/default1.png';
    
    // ถ้าไม่มี user_id ให้ใช้รูปเริ่มต้น
    if (!$user_id) {
        return $default_image;
    }

    try {
        // ดึงข้อมูลผู้ใช้
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user['profile_picture']) {
            // กรณีเป็นรูปเริ่มต้นของระบบ
            if (strpos($user['profile_picture'], 'default') === 0) {
                $system_image = '../assets/images/default_profiles/' . $user['profile_picture'];
                return file_exists($system_image) ? $system_image : $default_image;
            }
            
            // กรณีเป็นรูปที่ผู้ใช้อัพโหลด
            $upload_path = '../uploads/profile_pictures/' . $user['profile_picture'];
            return file_exists($upload_path) ? $upload_path : $default_image;
        }
    } catch (Exception $e) {
        error_log("Error in getProfileImage: " . $e->getMessage());
    }

    // กรณีมีปัญหาหรือไม่พบรูป ให้ใช้รูปเริ่มต้น
    return $default_image;
}

// เพิ่มฟังก์ชันสำหรับแสดงเวลาแบบ relative time
function timeAgo($timestamp) {
    $datetime = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($datetime);
    
    if ($interval->y > 0) {
        return $interval->y . ' ปีที่แล้ว';
    }
    if ($interval->m > 0) {
        return $interval->m . ' เดือนที่แล้ว';
    }
    if ($interval->d > 0) {
        return $interval->d . ' วันที่แล้ว';
    }
    if ($interval->h > 0) {
        return $interval->h . ' ชั่วโมงที่แล้ว';
    }
    if ($interval->i > 0) {
        return $interval->i . ' นาทีที่แล้ว';
    }
    
    return 'เมื่อสักครู่';
}
?>
