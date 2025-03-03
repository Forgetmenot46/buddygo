<?php
require_once 'config/config.php';

// SQL สำหรับสร้างตาราง chat_groups
$sql_chat_groups = "CREATE TABLE IF NOT EXISTS `chat_groups` (
    `group_id` INT PRIMARY KEY AUTO_INCREMENT,
    `post_id` INT NOT NULL,
    `group_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL สำหรับสร้างตาราง chat_messages
$sql_chat_messages = "CREATE TABLE IF NOT EXISTS `chat_messages` (
    `message_id` INT PRIMARY KEY AUTO_INCREMENT,
    `group_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`group_id`) REFERENCES `chat_groups`(`group_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL สำหรับสร้างตาราง chat_group_members
$sql_chat_members = "CREATE TABLE IF NOT EXISTS `chat_group_members` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `group_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`group_id`) REFERENCES `chat_groups`(`group_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_member` (`group_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    // ลบตารางเก่าถ้ามีอยู่
    $conn->query("DROP TABLE IF EXISTS `chat_messages`");
    $conn->query("DROP TABLE IF EXISTS `chat_group_members`");
    $conn->query("DROP TABLE IF EXISTS `chat_groups`");
    
    // สร้างตารางใหม่
    if ($conn->query($sql_chat_groups)) {
        echo "สร้างตาราง chat_groups สำเร็จ<br>";
    }

    if ($conn->query($sql_chat_messages)) {
        echo "สร้างตาราง chat_messages สำเร็จ<br>";
    }

    if ($conn->query($sql_chat_members)) {
        echo "สร้างตาราง chat_group_members สำเร็จ<br>";
    }

    echo "สร้างตารางทั้งหมดสำเร็จ!";
} catch (Exception $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}

$conn->close();
?> 