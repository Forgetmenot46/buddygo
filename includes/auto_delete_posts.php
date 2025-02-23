<?php
require_once __DIR__ . '/../config/config.php';

// ฟังก์ชันลบโพสต์ที่หมดอายุ
function deleteExpiredPosts() {
    global $conn;
    
    try {
        // เริ่ม transaction
        $conn->begin_transaction();

        // ดึงรายการโพสต์ที่หมดอายุ
        $expired_posts_sql = "SELECT p.post_id, p.title, p.image_path, 
                                    GROUP_CONCAT(DISTINCT pm.user_id) as member_ids
                            FROM community_posts p
                            LEFT JOIN post_members pm ON p.post_id = pm.post_id 
                            WHERE p.activity_date < CURDATE() 
                            AND p.status = 'active'
                            GROUP BY p.post_id";
        
        $expired_posts = $conn->query($expired_posts_sql);

        while ($post = $expired_posts->fetch_assoc()) {
            // ลบรูปภาพถ้ามี
            if (!empty($post['image_path'])) {
                $image_path = __DIR__ . "/../uploads/activity_images/" . $post['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // อัพเดตสถานะโพสต์เป็น 'expired'
            $update_sql = "UPDATE community_posts 
                          SET status = 'expired' 
                          WHERE post_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $post['post_id']);
            $update_stmt->execute();

            // แจ้งเตือนสมาชิกทั้งหมดในโพสต์
            if (!empty($post['member_ids'])) {
                $member_ids = explode(',', $post['member_ids']);
                $notification_sql = "INSERT INTO notifications 
                                   (user_id, type, message, post_id) 
                                   VALUES (?, 'post_expired', ?, ?)";
                $notify_stmt = $conn->prepare($notification_sql);
                
                foreach ($member_ids as $member_id) {
                    $message = "กิจกรรม \"{$post['title']}\" หมดเวลาแล้วและถูกย้ายไปยังประวัติกิจกรรม";
                    $notify_stmt->bind_param("isi", $member_id, $message, $post['post_id']);
                    $notify_stmt->execute();
                }
            }
        }

        // บันทึกประวัติการทำงาน
        $log_sql = "INSERT INTO cron_jobs (job_name, status, last_run) 
                    VALUES ('delete_expired_posts', 'completed', NOW())";
        $conn->query($log_sql);

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        // บันทึก error
        $error_sql = "INSERT INTO cron_jobs (job_name, status, last_run) 
                     VALUES ('delete_expired_posts', ?, NOW())";
        $error_stmt = $conn->prepare($error_sql);
        $error_message = 'error: ' . $e->getMessage();
        $error_stmt->bind_param("s", $error_message);
        $error_stmt->execute();
        return false;
    }
}

// เรียกใช้ฟังก์ชัน
deleteExpiredPosts(); 