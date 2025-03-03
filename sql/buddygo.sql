-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2025 at 04:48 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `buddygo`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_expired_groups` ()   BEGIN
    -- Update groups to expired where travel date has passed
    UPDATE community_posts p
    SET p.status = 'expired'
    WHERE p.activity_date < CURDATE() 
    AND p.status = 'active';
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','closed','deleted') DEFAULT 'active',
  `max_members` int(11) DEFAULT 0,
  `current_members` int(11) DEFAULT 1,
  `activity_image` varchar(255) NOT NULL,
  `activity_date` date DEFAULT NULL,
  `activity_time` time DEFAULT NULL,
  `post_local` varchar(255) NOT NULL,
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `title`, `description`, `user_id`, `created_at`, `updated_at`, `status`, `max_members`, `current_members`, `activity_image`, `activity_date`, `activity_time`, `post_local`, `view_count`) VALUES
(100, 'ฟหก', 'กฟหกฟหกฟห', 18, '2025-03-01 15:12:20', '2025-03-01 15:48:16', 'active', 5, 1, '67c323d4eb8d0.jpg', '2025-03-11', '12:15:00', 'ฟหกฟหก', 100),
(101, 'กฟห', 'กฟหฟหกกฟหฟหก', 18, '2025-03-01 15:15:36', '2025-03-01 15:47:50', 'active', 3, 1, '67c324983fdc1.jpg', '2025-03-06', '12:18:00', 'ฟหกกฟหฟหก', 5),
(102, 'กฟห', 'ฟกหหกฟกฟหกฟห', 18, '2025-03-01 15:17:20', '2025-03-01 15:17:20', 'active', 3, 1, '67c325004b8e6.jpg', '2025-03-05', '13:17:00', 'ฟหกกฟหฟหก', 0),
(103, 'ไปดูกัปตันasddasasdds', 'ฟหกฟหกกฟ', 18, '2025-03-01 15:17:38', '2025-03-01 15:18:08', 'active', 3, 1, '67c32512cc290.jpg', '2025-03-06', '12:20:00', 'ฟหกฟหกกฟหกห', 3),
(104, 'กหหกกหฟกหฟกห', 'ฟหกฟหกหกฟกห', 18, '2025-03-01 15:22:11', '2025-03-01 15:22:11', 'active', 3, 1, '67c3262369f6a.jpg', '2025-03-05', '22:25:00', 'ฟหกฟกห', 0);

-- --------------------------------------------------------

--
-- Table structure for table `countriesphone`
--

CREATE TABLE `countriesphone` (
  `country_id` int(11) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `country_phone_id` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countriesphone`
--

INSERT INTO `countriesphone` (`country_id`, `country_name`, `country_phone_id`) VALUES
(1, 'Thailand', '+66'),
(2, 'United States', '+1'),
(3, 'United Kingdom', '+44'),
(4, 'Japan', '+81'),
(5, 'China', '+86'),
(6, 'Spain', '+34'),
(7, 'France', '+33'),
(8, 'Germany', '+49'),
(9, 'Italy', '+39'),
(10, 'South Korea', '+82'),
(11, 'Portugal', '+351');

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` int(11) NOT NULL,
  `interest_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interests`
--

INSERT INTO `interests` (`id`, `interest_name`) VALUES
(7, 'Art (ศิลปะ)'),
(8, 'Camping (แคมปิ้ง)'),
(4, 'Cooking (ทำอาหาร)'),
(9, 'Cycling (ปั่นจักรยาน)'),
(16, 'Dancing (เต้น)'),
(20, 'Diving (ดำน้ำ)'),
(10, 'DIY Projects (งานประดิษฐ์)'),
(2, 'Fitness (ฟิตเนส)'),
(15, 'Gaming (เกม)'),
(5, 'Hiking (เดินป่า)'),
(18, 'Meditation (สมาธิ)'),
(19, 'Mountain Climbing (ปีนเขา)'),
(6, 'Movies (ภาพยนตร์)'),
(1, 'Music (ดนตรี)'),
(3, 'Photography (การถ่ายภาพ)'),
(14, 'Reading (การอ่าน)'),
(12, 'Sports (กีฬา)'),
(13, 'Swimming (ว่ายน้ำ)'),
(11, 'Travel (ท่องเที่ยว)'),
(17, 'Yoga (โยคะ)');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `language_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language_name`) VALUES
(3, 'Chinese'),
(1, 'English'),
(4, 'Japanese'),
(5, 'Spanish'),
(2, 'Thai');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `from_user_id`, `post_id`, `type`, `message`, `qr_code`, `status`, `is_read`, `created_at`) VALUES
(84, 19, 19, NULL, 'phone_verification', 'ผู้ใช้ Admin2 ต้องการยืนยันเบอร์มือถือ: 1239506984', NULL, NULL, 0, '2025-03-01 14:44:42'),
(85, 18, 18, NULL, 'phone_verification', 'ผู้ใช้ Admin ต้องการยืนยันเบอร์มือถือ: 1234567890', NULL, NULL, 0, '2025-03-01 14:48:07'),
(86, 18, 18, NULL, 'phone_verification', 'ผู้ใช้ Admin ต้องการยืนยันเบอร์มือถือ: 1234567890', NULL, NULL, 0, '2025-03-01 14:48:14');

-- --------------------------------------------------------

--
-- Table structure for table `post_interests`
--

CREATE TABLE `post_interests` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_interests`
--

INSERT INTO `post_interests` (`id`, `post_id`, `interest_id`, `created_at`) VALUES
(309, 100, 4, '2025-03-01 15:12:21'),
(310, 101, 8, '2025-03-01 15:15:36'),
(311, 101, 15, '2025-03-01 15:15:36'),
(312, 101, 3, '2025-03-01 15:15:36'),
(313, 102, 9, '2025-03-01 15:17:20'),
(314, 102, 5, '2025-03-01 15:17:20'),
(315, 102, 14, '2025-03-01 15:17:20'),
(316, 103, 4, '2025-03-01 15:17:38'),
(317, 103, 9, '2025-03-01 15:17:38'),
(318, 103, 5, '2025-03-01 15:17:38'),
(319, 104, 9, '2025-03-01 15:22:11');

-- --------------------------------------------------------

--
-- Table structure for table `post_members`
--

CREATE TABLE `post_members` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('interested','confirmed','cancelled') NOT NULL DEFAULT 'interested'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_members`
--

INSERT INTO `post_members` (`post_id`, `user_id`, `joined_at`, `status`) VALUES
(100, 18, '2025-03-01 15:12:20', 'confirmed'),
(101, 18, '2025-03-01 15:15:36', 'confirmed'),
(102, 18, '2025-03-01 15:17:20', 'confirmed'),
(103, 18, '2025-03-01 15:17:38', 'confirmed'),
(104, 18, '2025-03-01 15:22:11', 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `verified_status` tinyint(1) DEFAULT 0,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT 'default1.png',
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `nickname`, `birthdate`, `country_id`, `phone_number`, `email`, `password`, `gender`, `verified_status`, `last_login`, `is_admin`, `phone_verified`, `created_at`, `updated_at`, `role`, `profile_picture`, `status`) VALUES
(18, 'Real', 'Admin', 'Admin', NULL, '1975-07-16', 1, '1234567890', '12345Tester6789@gmail.com', '$2y$10$N1Tr/kIoAD7hPlHUeYpehurnRQVuIKOUDnL.YjHBcpPhsxBmCnAJC', 'male', 1, '2025-02-28 16:52:34', 1, 0, '2025-02-28 16:52:34', '2025-03-01 15:47:10', 'user', 'default2.png', 'active'),
(19, 'Realนะ', 'Admin2', 'Admin2', NULL, '2025-03-05', 1, '1239506984', 'wwwAdmin2@gmail.com', '$2y$10$6CvmU9RLqNJL5pduDorhF.5dQ.xI1NeHtkW5hSnIEey04Fnjz9gvi', 'female', 0, '2025-02-28 17:46:00', 0, 0, '2025-02-28 17:46:00', '2025-03-01 10:17:05', 'user', 'avatar.png', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `user_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_interests`
--

INSERT INTO `user_interests` (`user_id`, `interest_id`) VALUES
(18, 1),
(18, 4),
(18, 6),
(18, 7),
(18, 8),
(18, 9),
(18, 13),
(18, 17),
(19, 8),
(19, 9);

-- --------------------------------------------------------

--
-- Table structure for table `user_languages`
--

CREATE TABLE `user_languages` (
  `user_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_languages`
--

INSERT INTO `user_languages` (`user_id`, `language_id`) VALUES
(18, 1),
(18, 2),
(18, 3),
(19, 3),
(19, 4);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `countriesphone`
--
ALTER TABLE `countriesphone`
  ADD PRIMARY KEY (`country_id`),
  ADD UNIQUE KEY `country_name` (`country_name`),
  ADD UNIQUE KEY `country_phone_id` (`country_phone_id`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interest_name` (`interest_name`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language_name` (`language_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_interests`
--
ALTER TABLE `post_interests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `interest_id` (`interest_id`);

--
-- Indexes for table `post_members`
--
ALTER TABLE `post_members`
  ADD PRIMARY KEY (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD PRIMARY KEY (`user_id`,`interest_id`),
  ADD KEY `interest_id` (`interest_id`);

--
-- Indexes for table `user_languages`
--
ALTER TABLE `user_languages`
  ADD PRIMARY KEY (`user_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `post_id` (`post_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `countriesphone`
--
ALTER TABLE `countriesphone`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `post_interests`
--
ALTER TABLE `post_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_interests`
--
ALTER TABLE `post_interests`
  ADD CONSTRAINT `post_interests_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_interests_ibfk_2` FOREIGN KEY (`interest_id`) REFERENCES `interests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_members`
--
ALTER TABLE `post_members`
  ADD CONSTRAINT `post_members_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countriesphone` (`country_id`);

--
-- Constraints for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_interests_ibfk_2` FOREIGN KEY (`interest_id`) REFERENCES `interests` (`id`);

--
-- Constraints for table `user_languages`
--
ALTER TABLE `user_languages`
  ADD CONSTRAINT `user_languages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_languages_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_group_members;
DROP TABLE IF EXISTS chat_groups;

-- Create chat_groups table
CREATE TABLE chat_groups (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE
);

-- Create chat_group_members table
CREATE TABLE chat_group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (group_id, user_id)
);

-- Create chat_messages table
CREATE TABLE chat_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 