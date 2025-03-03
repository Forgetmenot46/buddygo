-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2025 at 12:14 PM
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
    UPDATE chat_groups g
    JOIN community_posts p ON g.post_id = p.post_id
    SET g.status = 'expired'
    WHERE p.travel_date < CURDATE() 
    AND g.status = 'active';
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chat_groups`
--

CREATE TABLE `chat_groups` (
  `group_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_group_members`
--

CREATE TABLE `chat_group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(105, 'ไปตีแบดกันคั้บ', 'สนามแบดคลองหก ข้างหลังมอ ต้องเอาแบดไปเองนะค้าบ', 20, '2025-03-03 05:27:57', '2025-03-03 05:36:26', 'active', 4, 1, 'default.jpg', '2025-03-11', '18:00:00', 'สนามแบดคอลงหก', 1),
(106, 'ไปคาเฟ่กันค่าาา', 'ไปวาดรูป ที่คาเฟ่หมากันค่า ', 21, '2025-03-03 05:32:25', '2025-03-03 05:38:25', 'active', 3, 2, 'default.jpg', '2025-03-20', '11:30:00', 'สวนจตุจักร', 2),
(107, 'อยากไปแคมปิ้ง', 'ไปแคมปิ้ง กางเต็นท์นอนที่ข้างน้ำตก', 22, '2025-03-03 05:36:02', '2025-03-03 05:36:02', 'active', 5, 1, 'default.jpg', '2025-03-29', '07:00:00', 'นครนายก', 0);

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
(86, 18, 18, NULL, 'phone_verification', 'ผู้ใช้ Admin ต้องการยืนยันเบอร์มือถือ: 1234567890', NULL, NULL, 0, '2025-03-01 14:48:14'),
(89, 21, 22, 106, 'join_request', 'minnie สนใจเข้าร่วมกิจกรรม ไปคาเฟ่กันค่าาา', NULL, 'confirmed', 0, '2025-03-03 05:37:32'),
(90, 21, 22, 106, 'request_confirmed', 'minnie ได้ยืนยันการเข้าร่วมกิจกรรม ไปคาเฟ่กันค่าาา', NULL, NULL, 0, '2025-03-03 05:37:54');

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
(320, 105, 12, '2025-03-03 05:27:57'),
(321, 106, 7, '2025-03-03 05:32:25'),
(322, 106, 3, '2025-03-03 05:32:25'),
(323, 106, 11, '2025-03-03 05:32:25'),
(324, 107, 8, '2025-03-03 05:36:02'),
(325, 107, 4, '2025-03-03 05:36:02'),
(326, 107, 5, '2025-03-03 05:36:02'),
(327, 107, 11, '2025-03-03 05:36:02');

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
(105, 20, '2025-03-03 05:27:57', 'confirmed'),
(106, 21, '2025-03-03 05:32:25', 'confirmed'),
(107, 22, '2025-03-03 05:36:02', 'confirmed');

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
(19, 'Realนะ', 'Admin2', 'Admin2', NULL, '2025-03-05', 1, '1239506984', 'wwwAdmin2@gmail.com', '$2y$10$6CvmU9RLqNJL5pduDorhF.5dQ.xI1NeHtkW5hSnIEey04Fnjz9gvi', 'female', 0, '2025-02-28 17:46:00', 0, 0, '2025-02-28 17:46:00', '2025-03-01 10:17:05', 'user', 'avatar.png', 'active'),
(20, 'Attachai', 'Singthong', 'Attachai5557', NULL, '1998-11-12', 1, '0993271546', 'wwe543216@hotmail.com', '$2y$10$2lGPejDAMgF1coupUNLoMOhTzDIc7.5lGuco8PrWK434zmDqgTXfu', 'male', 0, '2025-03-03 05:20:34', 0, 0, '2025-03-03 05:20:34', '2025-03-03 05:26:07', 'user', 'default1.png', 'active'),
(21, 'Gasira', 'Boonpilawan', 'kuromi', NULL, '2004-02-06', 1, '0658963256', 'kuromi@gmail.com', '$2y$10$3Sg5yxtXU13DHgxfHY1OVOJibJSfps14h8TKDMV5nWhv3Ew/rGNrO', 'female', 0, '2025-03-03 05:29:49', 0, 0, '2025-03-03 05:29:49', '2025-03-03 05:30:14', 'user', 'default2.png', 'active'),
(22, 'Kanokkorn', 'Sawekkaew', 'minnie', NULL, '2004-02-17', 1, '0956328974', 'minnie@gmail.com', '$2y$10$8qSzvaNzPnZCtho.2joYae8nPmhLSgjqdXVr9TbqalBzNTmN.cx36', 'female', 0, '2025-03-03 05:34:10', 0, 0, '2025-03-03 05:34:10', '2025-03-03 05:34:32', 'user', 'default3.png', 'active');

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
(19, 9),
(20, 12),
(20, 13),
(21, 4),
(21, 16),
(22, 4),
(22, 8),
(22, 9);

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
(19, 4),
(20, 2),
(21, 2),
(22, 1),
(22, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `post_id` (`post_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_groups`
--
ALTER TABLE `chat_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countriesphone`
--
ALTER TABLE `countriesphone`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD CONSTRAINT `chat_groups_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD CONSTRAINT `chat_group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
