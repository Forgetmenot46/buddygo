-- สร้างตาราง interests (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    interest_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- สร้างตาราง post_interests
CREATE TABLE IF NOT EXISTS post_interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    interest_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
);

-- สร้างตาราง post_members (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS post_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('joined', 'left', 'kicked') DEFAULT 'joined',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- สร้างตาราง chat_groups (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS chat_groups (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE
);

-- สร้างตาราง chat_group_members (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS chat_group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- เพิ่มข้อมูลตัวอย่างในตาราง interests (ถ้ายังไม่มี)
INSERT IGNORE INTO interests (interest_name) VALUES 
('ท่องเที่ยว'),
('กีฬา'),
('อาหาร'),
('ดนตรี'),
('ศิลปะ'),
('การถ่ายภาพ'),
('การเดินป่า'),
('การปีนเขา'),
('การดำน้ำ'),
('การตกปลา');

-- เพิ่มคอลัมน์ image ในตาราง community_posts
ALTER TABLE community_posts 
ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- เพิ่มตาราง cron_jobs สำหรับเก็บประวัติการทำงาน
CREATE TABLE IF NOT EXISTS cron_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(100) NOT NULL,
    last_run TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50)
);

-- เพิ่มคอลัมน์ image_path ในตาราง notifications
ALTER TABLE notifications 
ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- แก้ไขตาราง users เพื่อเพิ่มคอลัมน์สำหรับเก็บรูปภาพ
ALTER TABLE users
ADD COLUMN profile_image MEDIUMBLOB,
ADD COLUMN profile_image_type VARCHAR(50);

-- สร้างตาราง system_images สำหรับเก็บรูปของระบบ
CREATE TABLE IF NOT EXISTS system_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    image_name VARCHAR(50) NOT NULL,
    image_blob MEDIUMBLOB NOT NULL,
    image_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- แก้ไขตาราง users ให้เก็บแค่ชื่อไฟล์รูป
ALTER TABLE users
MODIFY COLUMN profile_picture VARCHAR(255) DEFAULT 'default1.png';

-- เพิ่มรูปเริ่มต้นของระบบ
INSERT INTO system_images (image_name, image_blob, image_type) 
SELECT 'default1.png', 
       LOAD_FILE('D:/xampp/htdocs/buddygo/assets/images/default_profiles/default1.png'), 
       'image/png'
WHERE NOT EXISTS (
    SELECT 1 FROM system_images WHERE image_name = 'default1.png'
);

INSERT INTO system_images (image_name, image_blob, image_type)
SELECT 'default2.png', 
       LOAD_FILE('D:/xampp/htdocs/buddygo/assets/images/default_profiles/default2.png'), 
       'image/png'
WHERE NOT EXISTS (
    SELECT 1 FROM system_images WHERE image_name = 'default2.png'
);

INSERT INTO system_images (image_name, image_blob, image_type)
SELECT 'default3.png', 
       LOAD_FILE('D:/xampp/htdocs/buddygo/assets/images/default_profiles/default3.png'), 
       'image/png'
WHERE NOT EXISTS (
    SELECT 1 FROM system_images WHERE image_name = 'default3.png'
); 