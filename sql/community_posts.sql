-- Create community posts table
CREATE TABLE IF NOT EXISTS community_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'closed', 'deleted') DEFAULT 'active',
    max_members INT DEFAULT 0,
    current_members INT DEFAULT 1,
    travel_date DATE,
    activity_date DATE,
    activity_time TIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create post members table
CREATE TABLE IF NOT EXISTS post_members (
    post_id INT,
    user_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'joined', 'interested', 'left', 'rejected') DEFAULT 'pending',
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create post comments table
CREATE TABLE IF NOT EXISTS post_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    user_id INT,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตาราง interests ถ้ายังไม่มี
CREATE TABLE IF NOT EXISTS interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    interest_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตาราง post_interests
CREATE TABLE IF NOT EXISTS post_interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    interest_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตาราง chat_groups ถ้ายังไม่มี
CREATE TABLE IF NOT EXISTS chat_groups (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตาราง chat_group_members ถ้ายังไม่มี
CREATE TABLE IF NOT EXISTS chat_group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตาราง notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    from_user_id INT NOT NULL,
    post_id INT,
    type ENUM('join_request', 'request_accepted', 'request_rejected', 'post_update', 'new_message') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- แก้ไขส่วนเพิ่มข้อมูลตัวอย่างในตาราง interests
INSERT IGNORE INTO interests (interest_name) VALUES 
('Music (ดนตรี)'),
('Fitness (ฟิตเนส)'),
('Photography (การถ่ายภาพ)'),
('Cooking (ทำอาหาร)'),
('Hiking (เดินป่า)'),
('Movies (ภาพยนตร์)'),
('Art (ศิลปะ)'),
('Camping (แคมปิ้ง)'),
('Cycling (ปั่นจักรยาน)'),
('DIY Projects (งานประดิษฐ์)'),
('Travel (ท่องเที่ยว)'),
('Sports (กีฬา)'),
('Swimming (ว่ายน้ำ)'),
('Reading (การอ่าน)'),
('Gaming (เกม)'),
('Dancing (เต้น)'),
('Yoga (โยคะ)'),
('Meditation (สมาธิ)'),
('Mountain Climbing (ปีนเขา)'),
('Diving (ดำน้ำ)');

-- อัพเดตตาราง post_members เพิ่มสถานะ pending
ALTER TABLE post_members MODIFY COLUMN status ENUM('pending', 'joined', 'interested', 'left', 'rejected') DEFAULT 'pending';
