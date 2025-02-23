-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 03:18 PM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `countriesphone`
--
ALTER TABLE `countriesphone`
  ADD PRIMARY KEY (`country_id`),
  ADD UNIQUE KEY `country_name` (`country_name`),
  ADD UNIQUE KEY `country_phone_id` (`country_phone_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `countriesphone`
--
ALTER TABLE `countriesphone`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
