<?php
require_once 'includes/db_connect.php';

// สร้างตาราง message_reads ถ้ายังไม่มี
$sql = "CREATE TABLE IF NOT EXISTS message_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    last_read_id INT NOT NULL DEFAULT 0,
    last_read_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
    UNIQUE KEY user_group (user_id, group_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table message_reads created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// สร้างฟังก์ชันสำหรับนับจำนวนข้อความที่ยังไม่ได้อ่าน
$sql = "CREATE OR REPLACE FUNCTION get_unread_count(p_user_id INT, p_group_id INT) 
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_last_read_id INT;
    DECLARE v_unread_count INT;
    
    -- หา last_read_id ของผู้ใช้
    SELECT COALESCE(last_read_id, 0) INTO v_last_read_id
    FROM message_reads
    WHERE user_id = p_user_id AND group_id = p_group_id;
    
    -- นับจำนวนข้อความที่ยังไม่ได้อ่าน
    SELECT COUNT(*) INTO v_unread_count
    FROM chat_messages
    WHERE group_id = p_group_id AND message_id > v_last_read_id AND user_id != p_user_id;
    
    RETURN v_unread_count;
END";

if ($conn->multi_query($sql) === TRUE) {
    echo "<br>Function get_unread_count created successfully";
} else {
    echo "<br>Error creating function: " . $conn->error;
}

$conn->close(); 