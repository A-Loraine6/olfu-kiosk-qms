-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2026 at 08:48 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u584867080_qms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `identifier` varchar(320) NOT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`identifier`),
  KEY `idx_login_attempts_last_attempt` (`last_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `window_levels` (
  `office_id` int(11) NOT NULL,
  `window_number` tinyint(3) UNSIGNED NOT NULL,
  `student_level` enum('shs','college','both') NOT NULL DEFAULT 'both',
  PRIMARY KEY (`office_id`,`window_number`),
  KEY `idx_window_levels_office_level` (`office_id`,`student_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(30, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torres\"}', '{\"full_name\":\"Liezel Mae Torress\"}', 'Updated staff member: Liezel Mae Torress', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 07:00:54'),
(31, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torress\"}', '{\"full_name\":\"Liezel Mae Torres\"}', 'Updated staff member: Liezel Mae Torres', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 07:06:17'),
(32, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquiry\"}', '{\"name\":\"Balance Inquirys\"}', 'Updated service \"Balance Inquirys\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 07:19:27'),
(33, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquirys\",\"office\":\"Accounting\"}', '{\"name\":\"Balance Inquiry\",\"office\":\"Admissions\"}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 07:19:39'),
(34, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"office\":\"Admissions\"}', '{\"office\":\"Accounting\"}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 07:19:55'),
(35, 1, 'STAFF_DEACTIVATE', 'USER', 2, '{\"is_active\":true}', '{\"is_active\":false}', 'Deactivated staff account: Liezel Mae Torres', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 22:12:12'),
(36, 1, 'STAFF_ACTIVATE', 'USER', 2, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Liezel Mae Torres', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 22:12:16'),
(37, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"open_time\":\"08:00\"}', '{\"open_time\":\"10:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 22:14:53'),
(38, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"open_time\":\"10:00\"}', '{\"open_time\":\"08:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 22:15:09'),
(39, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 21:15:10'),
(40, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 21:16:10'),
(41, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 21:16:13'),
(42, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:17:19'),
(43, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:17:21'),
(44, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 03:25:30'),
(45, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:04:02'),
(46, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 04:04:07'),
(47, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 05:31:56'),
(48, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 09:56:10'),
(49, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:08:15'),
(50, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:08:17'),
(51, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:08:25'),
(52, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:08:28'),
(53, 2, 'PASSWORD_CHANGE', 'USER', 2, NULL, NULL, 'Changed account password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:14:30'),
(54, 2, 'PASSWORD_CHANGE', 'USER', 2, NULL, NULL, 'Changed account password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 10:18:17'),
(55, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:16:27'),
(56, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:36:54'),
(57, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:36:57'),
(58, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:38:18'),
(59, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 01:38:38'),
(60, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:17:17'),
(61, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 10:17:55'),
(62, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 12:05:38'),
(63, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 13:37:23'),
(64, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 13:39:47'),
(65, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 13:45:17'),
(66, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 00:13:34'),
(67, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 01:26:02'),
(68, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 02:15:02'),
(69, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 06:50:21'),
(70, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 07:51:13'),
(71, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:03:32'),
(72, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:39:17'),
(73, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:43:22'),
(74, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:44:08'),
(75, 1, 'SERVICE_EDIT', 'SERVICE', 7, '{\"office\":\"Accounting\"}', '{\"office\":\"Registrar\"}', 'Updated service \"Incomplete Requirements\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:44:51'),
(76, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:46:00'),
(77, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 06:46:08'),
(78, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:16:37'),
(79, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:17:34'),
(80, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:22:02'),
(81, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:22:56'),
(82, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:23:39'),
(83, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:24:08'),
(84, 1, 'PROFILE_UPDATE', 'USER', 1, '{\"full_name\":\"Angel Loraine Reobilos\",\"email\":\"reobiloangel6@gmail.com\"}', '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloangel6@gmail.com\"}', 'Updated own profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-18 07:27:58'),
(85, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:15:16'),
(86, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:15:25'),
(87, 1, 'STAFF_ADD', 'USER', 3, NULL, '{\"full_name\":\"Liz Margaret Santiago\",\"email\":\"santiagolizmargaret4@gmail.com\",\"role\":\"staff\",\"office\":\"Admissions\"}', 'Added new staff member: Liz Margaret Santiago)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:15:54'),
(88, 1, 'STAFF_ADD', 'USER', 4, NULL, '{\"full_name\":\"Iris Remorosa\",\"email\":\"irisremorosa@gmail.com\",\"role\":\"staff\",\"office\":\"Registrar\"}', 'Added new staff member: Iris Remorosa)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:16:16'),
(89, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:16:50'),
(90, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:16:53'),
(91, 3, 'LOGOUT', 'user', 3, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:17:57'),
(92, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:18:02'),
(93, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:18:26'),
(94, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:18:49'),
(95, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:19:43'),
(96, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:19:46'),
(97, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"09:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:44:24'),
(98, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"09:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:44:54'),
(99, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"09:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:44:58'),
(100, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"09:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:45:07'),
(101, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"09:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:45:11'),
(102, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"09:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:45:14'),
(103, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:45:34'),
(104, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:48:19'),
(105, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:48:24'),
(106, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:50:34'),
(107, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:25:27'),
(108, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\",\"break_start\":\"\",\"break_end\":\"\"}', '{\"close_time\":\"23:00\",\"break_start\":\"19:25\",\"break_end\":\"19:30\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:26:21'),
(109, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"19:30\"}', '{\"break_end\":\"20:32\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:17:59'),
(110, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:26:33'),
(111, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"\"}', '{\"queue_cutoff\":\"20:28\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:27:51'),
(112, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"20:28\"}', '{\"queue_cutoff\":\"20:34\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:30:47'),
(113, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"20:34\"}', '{\"queue_cutoff\":\"22:34\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:35:43'),
(114, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"20:32\"}', '{\"break_end\":\"21:32\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:38:56'),
(115, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"22:34\"}', '{\"queue_cutoff\":\"20:34\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:43:12'),
(116, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"23:00\",\"break_start\":\"19:25\",\"break_end\":\"21:32\",\"queue_cutoff\":\"20:34\"}', '{\"close_time\":\"20:00\",\"break_start\":\"12:30\",\"break_end\":\"13:32\",\"queue_cutoff\":\"19:30\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:45:15'),
(117, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"20:00\"}', '{\"close_time\":\"21:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:49:12'),
(118, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"21:00\"}', '{\"close_time\":\"22:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:00:39'),
(119, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"19:30\"}', '{\"queue_cutoff\":\"21:41\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:01:21'),
(120, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"13:32\"}', '{\"break_end\":\"21:33\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:04:12'),
(121, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:15:17'),
(122, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"21:33\",\"queue_cutoff\":\"21:41\"}', '{\"break_end\":\"21:39\",\"queue_cutoff\":\"21:44\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:35:24'),
(123, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:36:21'),
(124, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:37:01'),
(125, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:37:32'),
(126, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:37:46'),
(127, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:38:15'),
(128, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:38:22'),
(129, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:39:15'),
(130, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:39:24'),
(131, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:39:33'),
(132, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:39:41'),
(133, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:47:29'),
(134, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:47:42'),
(135, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:47:55'),
(136, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:49:42'),
(137, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"22:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:50:03'),
(138, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"23:00\",\"break_end\":\"21:39\",\"queue_cutoff\":\"21:44\"}', '{\"close_time\":\"22:00\",\"break_end\":\"21:58\",\"queue_cutoff\":\"21:59\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:56:03'),
(139, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"23:00\",\"break_start\":\"\",\"break_end\":\"\",\"queue_cutoff\":\"\"}', '{\"close_time\":\"22:00\",\"break_start\":\"14:56\",\"break_end\":\"21:58\",\"queue_cutoff\":\"21:59\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:56:48'),
(140, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"23:00\",\"break_start\":\"\",\"break_end\":\"\",\"queue_cutoff\":\"\"}', '{\"close_time\":\"22:00\",\"break_start\":\"14:56\",\"break_end\":\"21:58\",\"queue_cutoff\":\"21:59\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:57:15'),
(141, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_end\":\"21:58\",\"queue_cutoff\":\"21:59\"}', '{\"break_end\":\"21:57\",\"queue_cutoff\":\"21:57\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:58:13'),
(142, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 13:58:52'),
(143, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:47:03'),
(144, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:47:06'),
(145, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:47:12'),
(146, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"22:00\",\"break_end\":\"21:58\"}', '{\"close_time\":\"23:00\",\"break_end\":\"22:50\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:48:03'),
(147, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"23:00\",\"queue_cutoff\":\"21:59\"}', '{\"close_time\":\"23:59\",\"queue_cutoff\":\"23:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:49:04'),
(148, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"22:50\"}', '{\"break_end\":\"22:52\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:51:47'),
(149, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"22:52\"}', '{\"break_end\":\"22:55\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:52:04'),
(150, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"23:59\",\"break_start\":\"12:30\",\"break_end\":\"22:55\",\"queue_cutoff\":\"23:00\"}', '{\"close_time\":\"17:00\",\"break_start\":\"12:00\",\"break_end\":\"13:00\",\"queue_cutoff\":\"12:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:53:46'),
(151, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 14:55:33'),
(152, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 15:01:04'),
(153, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 15:01:08'),
(154, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:22:46'),
(155, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:22:52'),
(156, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:23:03'),
(157, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:23:09'),
(158, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:26:08'),
(159, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:26:12'),
(160, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:36:07'),
(161, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:36:11'),
(162, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:36:59'),
(163, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:37:01'),
(164, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 1, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:37:02'),
(165, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 1, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:37:26'),
(166, 1, 'SERVICE_ADD', 'SERVICE', 9, NULL, '{\"name\":\"Enrollment of 2nd Course Students\",\"category\":\"regular\",\"description\":\"Processing of enrollment applications for students pursuing a second degree\",\"office\":\"Admissions\"}', 'Added new service \"Enrollment of 2nd Course Students\" for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:39:50'),
(167, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:39:57'),
(168, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:40:12'),
(169, 1, 'SERVICE_ADD', 'SERVICE', 10, NULL, '{\"name\":\"Enrollment of Transferees\",\"category\":\"regular\",\"description\":\"Enrollment assistance for students transferring from another school.\",\"office\":\"Admissions\"}', 'Added new service \"Enrollment of Transferees\" for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:40:40'),
(170, 1, 'SERVICE_ADD', 'SERVICE', 11, NULL, '{\"name\":\"Enrollment of New Incoming Grade 11 Students\",\"category\":\"complicated\",\"description\":\"Admission and enrollment for new Grade 11 students.\",\"office\":\"Admissions\"}', 'Added new service \"Enrollment of New Incoming Grade 11 Students\" for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:40:56'),
(171, 1, 'SERVICE_ADD', 'SERVICE', 12, NULL, '{\"name\":\"Enrollment of College Freshmen\",\"category\":\"regular\",\"description\":\"Admission and enrollment for first-year college students.\",\"office\":\"Admissions\"}', 'Added new service \"Enrollment of College Freshmen\" for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:41:10'),
(172, 1, 'SERVICE_EDIT', 'SERVICE', 11, '{\"category\":\"complicated\"}', '{\"category\":\"regular\"}', 'Updated service \"Enrollment of New Incoming Grade 11 Students\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:41:15'),
(173, 1, 'SERVICE_ADD', 'SERVICE', 13, NULL, '{\"name\":\"Certificate of Enrollment\",\"category\":\"simple\",\"description\":\"Issuance of proof of current enrollment.\",\"office\":\"Registrar\"}', 'Added new service \"Certificate of Enrollment\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:41:29'),
(174, 1, 'SERVICE_ADD', 'SERVICE', 14, NULL, '{\"name\":\"Certificate of Grades\",\"category\":\"simple\",\"description\":\"Issuance of a copy of student grades.\",\"office\":\"Registrar\"}', 'Added new service \"Certificate of Grades\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:41:41'),
(175, 1, 'SERVICE_ADD', 'SERVICE', 15, NULL, '{\"name\":\"Transcript of Records Request\",\"category\":\"regular\",\"description\":\"Processing request for official transcript of records.\",\"office\":\"Registrar\"}', 'Added new service \"Transcript of Records Request\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:42:02'),
(176, 1, 'SERVICE_ADD', 'SERVICE', 16, NULL, '{\"name\":\"Subject Validation\",\"category\":\"complicated\",\"description\":\"Evaluation of subjects for equivalency or credit.\",\"office\":\"Registrar\"}', 'Added new service \"Subject Validation\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:42:16'),
(177, 1, 'SERVICE_ADD', 'SERVICE', 17, NULL, '{\"name\":\"Diploma Releasing\",\"category\":\"simple\",\"description\":\"Release of prepared diplomas to graduates.\",\"office\":\"Registrar\"}', 'Added new service \"Diploma Releasing\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:42:29'),
(178, 1, 'SERVICE_ADD', 'SERVICE', 18, NULL, '{\"name\":\"Transfer Credentials\",\"category\":\"regular\",\"description\":\"Processing documents for students transferring out.\",\"office\":\"Registrar\"}', 'Added new service \"Transfer Credentials\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:42:43'),
(179, 1, 'SERVICE_ADD', 'SERVICE', 19, NULL, '{\"name\":\"Exit Clearance\",\"category\":\"regular\",\"description\":\"Completion of final clearance requirements.\",\"office\":\"Registrar\"}', 'Added new service \"Exit Clearance\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:42:54'),
(180, 1, 'SERVICE_ADD', 'SERVICE', 20, NULL, '{\"name\":\"Enrollment Concerns\",\"category\":\"simple\",\"description\":\"Assistance with minor enrollment issues.\",\"office\":\"Registrar\"}', 'Added new service \"Enrollment Concerns\" for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:43:04'),
(181, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 1, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:43:34'),
(182, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 1, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 01:43:47'),
(183, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:42:19'),
(184, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"windows\":[]}', '{\"windows\":[1,2,3]}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:42:52'),
(185, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"windows\":[1,2,3]}', '{\"windows\":[]}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:43:33'),
(186, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"windows\":[]}', '{\"windows\":[1,2]}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:43:42'),
(187, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"windows\":[1,2]}', '{\"windows\":[1]}', 'Updated service \"Balance Inquiry\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:47:13'),
(188, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 03:18:00'),
(189, 1, 'STAFF_EDIT', 'USER', 2, '{\"window_number\":null}', '{\"window_number\":1}', 'Updated staff member: Liezel Mae Torres', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 03:30:15'),
(190, 1, 'SERVICE_EDIT', 'SERVICE', 3, '{\"windows\":[]}', '{\"windows\":[1]}', 'Updated service \"Tuition Fee\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 03:30:35'),
(191, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 03:41:54'),
(192, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 03:41:57'),
(193, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 04:05:28'),
(194, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 13:04:50'),
(195, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 13:05:31'),
(196, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 13:05:39'),
(197, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 13:06:11'),
(198, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 13:06:15'),
(199, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:21:50'),
(200, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:21:57'),
(201, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:22:02'),
(202, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:25:56'),
(203, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:25:58'),
(204, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:53:48'),
(205, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:53:52'),
(206, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:27:04'),
(207, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:27:06'),
(208, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:27:09'),
(209, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:27:12'),
(210, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:43:46'),
(211, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 03:43:48'),
(212, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:04:28'),
(213, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:04:33'),
(214, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:13:13'),
(215, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:13:15'),
(216, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:15:26');
INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(217, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:15:30'),
(218, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:18:40'),
(219, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"break_start\":\"14:56\",\"break_end\":\"21:58\"}', '{\"break_start\":\"12:00\",\"break_end\":\"13:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:54:52'),
(220, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"queue_cutoff\":\"21:59\"}', '{\"queue_cutoff\":\"\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:55:10'),
(221, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"queue_cutoff\":\"12:00\"}', '{\"queue_cutoff\":\"\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:55:12'),
(222, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_start\":\"14:56\",\"break_end\":\"21:57\",\"queue_cutoff\":\"21:57\"}', '{\"break_start\":\"12:00\",\"break_end\":\"13:00\",\"queue_cutoff\":\"\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:55:28'),
(223, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_end\":\"13:00\"}', '{\"break_end\":\"14:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:55:33'),
(224, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_end\":\"14:00\"}', '{\"break_end\":\"13:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 05:55:45'),
(225, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 07:54:06'),
(226, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 09:38:29'),
(227, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 09:38:50'),
(228, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 09:38:56'),
(229, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:28:59'),
(230, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"22:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:36:19'),
(231, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"22:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:36:22'),
(232, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:43:31'),
(233, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:43:33'),
(234, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 12:52:05'),
(235, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 13:44:24'),
(236, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 13:44:27'),
(237, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 13:44:31'),
(238, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 13:44:43'),
(239, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 13:44:46'),
(240, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 01:25:23'),
(241, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 02:32:29'),
(242, 1, 'STAFF_ADD', 'USER', 5, NULL, '{\"full_name\":\"Angel Loraine Reobilo\",\"email\":\"reobiloloraine@gmail.com\",\"role\":\"staff\",\"office\":\"Accounting\",\"window_number\":null}', 'Added new staff member: Angel Loraine Reobilo)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 02:40:15'),
(243, 1, 'STAFF_EDIT', 'USER', 4, '{\"window_number\":null}', '{\"window_number\":1}', 'Updated staff member: Iris Remorosa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 02:42:17'),
(244, 1, 'STAFF_EDIT', 'USER', 3, '{\"window_number\":null}', '{\"window_number\":1}', 'Updated staff member: Liz Margaret Santiago', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 02:42:23'),
(245, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 03:34:11'),
(246, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:04:20'),
(247, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:09:00'),
(248, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:11:02'),
(249, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:11:04'),
(250, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:12:49'),
(251, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:12:51'),
(252, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:13:51'),
(253, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:13:53'),
(254, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:17:12'),
(255, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:17:14'),
(256, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:17:23'),
(257, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:17:25'),
(258, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:37:20'),
(259, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:37:22'),
(260, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:38:02'),
(261, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:38:04'),
(262, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:38:07'),
(263, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:38:16'),
(264, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:51:29'),
(265, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 05:51:31'),
(266, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:01:22'),
(267, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:01:24'),
(268, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:23'),
(269, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:27'),
(270, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:36'),
(271, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:42'),
(272, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:51'),
(273, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:04:53'),
(274, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:05:01'),
(275, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:05:03'),
(276, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:48:46'),
(277, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:48:48'),
(278, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:49:12'),
(279, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:49:14'),
(280, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:49:22'),
(281, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:50:41'),
(282, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:52:43'),
(283, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:52:45'),
(284, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:54:39'),
(285, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:54:41'),
(286, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:56:02'),
(287, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:56:04'),
(288, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:57:39'),
(289, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:57:42'),
(290, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:57:46'),
(291, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 06:57:54'),
(292, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:01:44'),
(293, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:02:03'),
(294, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:02:05'),
(295, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:02:27'),
(296, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:02:30'),
(297, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:03:35'),
(298, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:03:39'),
(299, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:03:49'),
(300, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:05:33'),
(301, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:05:56'),
(302, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:05:58'),
(303, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:07:39'),
(304, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:07:41'),
(305, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:07:46'),
(306, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:07:48'),
(307, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:07:51'),
(308, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:10:26'),
(309, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:10:38'),
(310, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:10:59'),
(311, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:11:26'),
(312, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:11:27'),
(313, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:11:31'),
(314, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:19:55'),
(315, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:29:18'),
(316, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:29:21'),
(317, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:02:55'),
(318, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"19:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:21:28'),
(319, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"19:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:21:33'),
(320, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"19:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:21:35'),
(321, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:28:10'),
(322, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 10:28:13'),
(323, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:20:50'),
(324, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:20:53'),
(325, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:21:02'),
(326, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:21:14'),
(327, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:21:33'),
(328, 1, 'STAFF_EDIT', 'USER', 5, '{\"office\":\"Accounting\"}', '{\"office\":\"Admissions\"}', 'Updated staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:21:45'),
(329, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:22:07'),
(330, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:22:11'),
(331, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:50:23'),
(332, 1, 'STAFF_EDIT', 'USER', 5, '{\"office\":\"Admissions\"}', '{\"office\":\"Accounting\"}', 'Updated staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:50:30'),
(333, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:50:35'),
(334, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"19:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:54:55'),
(335, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"19:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Admissions\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:54:57'),
(336, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"19:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Registrar\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 11:54:59'),
(337, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 12:01:13'),
(338, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 12:01:16'),
(339, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 12:22:58'),
(340, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:39:15'),
(341, 1, 'STAFF_ADD', 'USER', 6, NULL, '{\"full_name\":\"Lizelda Marie\",\"email\":\"ltorres6129ant@student.fatima.edu.ph\",\"role\":\"Supervisor\",\"office\":\"Admissions\",\"window_number\":null}', 'Added new staff member: Lizelda Marie)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:41:16'),
(342, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:43:07'),
(343, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:43:11'),
(344, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:45:07'),
(345, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:45:10'),
(346, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:46:01'),
(347, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:46:06'),
(348, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:47:43'),
(349, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:47:47'),
(350, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:55:23'),
(351, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:55:25'),
(352, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:55:44'),
(353, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:55:47'),
(354, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:56:01'),
(355, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 03:56:05'),
(356, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:17:38'),
(357, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:17:41'),
(358, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:22:13'),
(359, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:22:17'),
(360, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:24:38'),
(361, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:24:42'),
(362, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:24:48'),
(363, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:24:53'),
(364, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:25:07'),
(365, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:25:11'),
(366, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:30:41'),
(367, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:30:44'),
(368, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:30:51'),
(369, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:30:55'),
(370, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:31:48'),
(371, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:31:52'),
(372, 1, 'STAFF_DEACTIVATE', 'USER', 5, '{\"is_active\":true}', '{\"is_active\":false}', 'Deactivated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:31:59'),
(373, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:32:38'),
(374, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:32:51'),
(375, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:32:57'),
(376, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:33:03'),
(377, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:34:31'),
(378, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:34:37'),
(379, 1, 'STAFF_ACTIVATE', 'USER', 5, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:35:32'),
(380, 1, 'STAFF_DEACTIVATE', 'USER', 5, '{\"is_active\":true}', '{\"is_active\":false}', 'Deactivated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:35:35'),
(381, 1, 'STAFF_ACTIVATE', 'USER', 5, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:35:39'),
(382, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 04:35:59'),
(383, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:07:32'),
(384, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:10:22'),
(385, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:10:38'),
(386, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:11:58'),
(387, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:12:34'),
(388, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"idle_timeout\":60}', '{\"idle_timeout\":10}', 'Updated kiosk settings', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:12:42'),
(389, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"idle_timeout\":10}', '{\"idle_timeout\":60}', 'Updated kiosk settings', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:14:53'),
(390, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:21:08'),
(391, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:22:03'),
(392, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:22:55'),
(393, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:51:49'),
(394, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:54:02'),
(395, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:54:12'),
(396, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:54:13'),
(397, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:54:56'),
(398, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '122.54.201.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 00:55:49'),
(399, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:45:31'),
(400, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:46:08'),
(401, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Admissions\"', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:46:14'),
(402, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"23:00\"}', 'Updated office schedule for \"Registrar\"', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:46:19'),
(403, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:46:25'),
(404, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:53:06'),
(405, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:55:46'),
(406, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 13:56:10'),
(407, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 01:22:35'),
(408, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 01:29:50'),
(409, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 01:59:45'),
(410, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:06:20'),
(411, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.87.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 10:07:07'),
(412, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.85.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:32:07'),
(413, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.85.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:57:26'),
(414, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.85.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:51:33'),
(415, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '110.93.85.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 14:22:11'),
(416, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:12:41'),
(417, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:16:49'),
(418, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"23:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:18:40'),
(419, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"close_time\":\"23:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Admissions\"', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:18:43'),
(420, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"23:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Registrar\"', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:18:45'),
(421, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '136.158.10.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-13 12:28:40'),
(422, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '136.158.10.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-13 12:29:14'),
(423, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"21:00\"}', 'Updated office schedule for \"Accounting\"', '136.158.10.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-13 12:29:35'),
(424, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '136.158.10.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-13 12:30:20'),
(425, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 01:14:06'),
(426, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '136.158.10.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 01:56:36'),
(427, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 02:29:50'),
(428, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 06:32:07'),
(429, 1, 'SERVICE_ADD', 'SERVICE', 21, NULL, '{\"name\":\"Sample Service\",\"category\":\"simple\",\"description\":\"Sample service\",\"office\":\"Admissions\",\"windows\":[2]}', 'Added new service \"Sample Service\" for Admissions', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 06:32:29'),
(430, 1, 'SERVICE_DELETE', 'SERVICE', 21, NULL, NULL, 'Deleted service \"Sample Service\"', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 06:32:36'),
(431, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:47:37'),
(432, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:51:25');
INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(433, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:51:27'),
(434, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:51:29'),
(435, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:51:53'),
(436, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:52:04'),
(437, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:52:50'),
(438, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '110.93.94.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:57:31'),
(439, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:21:23'),
(440, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:25:37'),
(441, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:25:48'),
(442, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:26:02'),
(443, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:26:11'),
(444, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:26:40'),
(445, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:28:07'),
(446, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:35:10'),
(447, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:35:25'),
(448, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"13:00\"}', '{\"break_end\":\"12:30\"}', 'Updated office schedule for \"Accounting\"', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 04:35:37'),
(449, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:11:13'),
(450, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:11:23'),
(451, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:17:14'),
(452, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:17:22'),
(453, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:18:35'),
(454, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:18:46'),
(455, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"21:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:19:05'),
(456, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:19:42'),
(457, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:20:29'),
(458, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"12:30\"}', '{\"break_end\":\"13:00\"}', 'Updated office schedule for \"Accounting\"', '2405:8d40:4486:3f59:f5de:6f72:690e:e818', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 05:20:57'),
(459, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:35:13'),
(460, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"open_time\":\"08:00\"}', '{\"open_time\":\"07:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:35:17'),
(461, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:35:56'),
(462, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:35:59'),
(463, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:36:46'),
(464, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:36:49'),
(465, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"open_time\":\"07:00\"}', '{\"open_time\":\"08:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:36:55'),
(466, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:36:57'),
(467, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:40:23'),
(468, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"open_time\":\"08:00\"}', '{\"open_time\":\"07:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:40:29'),
(469, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:40:52'),
(470, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:40:56'),
(471, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 23:52:05'),
(472, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:18b0:dc88:3351:c16a', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 04:54:26'),
(473, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:18b0:dc88:3351:c16a', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 04:55:04'),
(474, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"13:00\"}', '{\"break_end\":\"12:30\"}', 'Updated office schedule for \"Accounting\"', '2405:8d40:4486:3f59:18b0:dc88:3351:c16a', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 04:55:32'),
(475, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '2405:8d40:4486:3f59:18b0:dc88:3351:c16a', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 04:55:40'),
(476, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4486:3f59:18b0:dc88:3351:c16a', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 04:56:10'),
(477, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:38:29'),
(478, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:38:44'),
(479, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:38:54'),
(480, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:39:26'),
(481, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:39:36'),
(482, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"20:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:39:44'),
(483, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:41:58'),
(484, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 11:42:01'),
(485, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 12:16:50'),
(486, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 12:16:59'),
(487, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"20:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 12:17:03'),
(488, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 12:17:13'),
(489, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 10:10:09'),
(490, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquiry\"}', '{\"name\":\"Balance Inquirys\"}', 'Updated service \"Balance Inquirys\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 10:10:22'),
(491, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:44:21'),
(492, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquirys\"}', '{\"name\":\"Balance Inquiry\"}', 'Updated service \"Balance Inquiry\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:44:29'),
(493, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquiry\"}', '{\"name\":\"Balance Inquirys\"}', 'Updated service \"Balance Inquirys\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:44:37'),
(494, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquirys\"}', '{\"name\":\"Balance Inquiry\"}', 'Updated service \"Balance Inquiry\"', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:44:44'),
(495, 1, 'SERVICE_RESTORE', 'SERVICE', 21, NULL, NULL, 'Restored service: Sample Service', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:44:51'),
(496, 1, 'SERVICE_DELETE', 'SERVICE', 21, NULL, NULL, 'Deleted service: Sample Service', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:45:07'),
(497, 1, 'SERVICE_TOGGLE_STATUS', 'SERVICE', 4, '{\"is_active\":true}', '{\"is_active\":false}', 'Service \"Balance Inquiry\" has been deactivated', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:48:29'),
(498, 1, 'SERVICE_TOGGLE_STATUS', 'SERVICE', 4, '{\"is_active\":false}', '{\"is_active\":true}', 'Service \"Balance Inquiry\" has been activated', '110.93.92.187', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 11:48:30'),
(499, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '136.158.10.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 21:35:16'),
(500, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:dd7e:9b3b:a671:f85d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:21:15'),
(501, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:dd7e:9b3b:a671:f85d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:22:27'),
(502, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '2405:8d40:4091:5513:dd7e:9b3b:a671:f85d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:22:41'),
(503, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:3d4f:b2f6:3940:421e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:26:58'),
(504, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 3, '{\"open_time\":\"08:00\"}', '{\"open_time\":\"07:00\"}', 'Updated office schedule for \"Admissions\"', '2405:8d40:4091:5513:3d4f:b2f6:3940:421e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:28:54'),
(505, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"open_time\":\"08:00\"}', '{\"open_time\":\"07:00\"}', 'Updated office schedule for \"Registrar\"', '2405:8d40:4091:5513:3d4f:b2f6:3940:421e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:28:56'),
(506, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:3d4f:b2f6:3940:421e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:29:40'),
(507, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 23:58:56'),
(508, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:00:35'),
(509, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:01:41'),
(510, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:05:31'),
(511, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Admissions', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:05:36'),
(512, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Admissions', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:05:38'),
(513, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Admissions', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:05:40'),
(514, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 3, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Admissions', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:06:48'),
(515, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:10:40'),
(516, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:11:07'),
(517, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:13:14'),
(518, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:13:35'),
(519, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:15:57'),
(520, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:17:56'),
(521, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:19:02'),
(522, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:19:38'),
(523, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '2405:8d40:4091:5513:f0d4:f258:375a:b338', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:19:59'),
(524, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 10:57:14'),
(525, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 10:58:28'),
(526, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 10:58:37'),
(527, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"20:00\"}', 'Updated office schedule for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 10:59:19'),
(528, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 12:44:10'),
(529, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"close_time\":\"20:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 12:44:16'),
(530, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 2, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', 'Queue mode switched to unified for Accounting', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 12:48:27'),
(531, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 2, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', 'Queue mode switched to service-based for Accounting', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 12:48:28'),
(532, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 02:13:23'),
(533, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 02:42:26'),
(534, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 02:42:31'),
(535, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 02:42:39'),
(536, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:38:00'),
(537, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:40:03'),
(538, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:40:15'),
(539, 3, 'LOGOUT', 'user', 3, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:40:30'),
(540, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:40:49'),
(541, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:41:16'),
(542, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:41:29'),
(543, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:42:02'),
(544, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 03:42:06'),
(545, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:19:07'),
(546, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:19:10'),
(547, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:19:20'),
(548, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:19:24'),
(549, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:22:10'),
(550, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:22:15'),
(551, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"break_end\":\"12:30\"}', '{\"break_end\":\"13:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:27:21'),
(552, 1, 'SETTINGS_QUEUE_UPDATE', 'OFFICE', 1, '{\"max_queue_limit\":0}', '{\"max_queue_limit\":1}', 'Updated queue configuration for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:27:51'),
(553, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:27:58'),
(554, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_end\":\"13:00\"}', '{\"break_end\":\"12:20\"}', 'Updated office schedule for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:28:38'),
(555, 1, 'SETTINGS_QUEUE_UPDATE', 'OFFICE', 1, '{\"max_queue_limit\":1}', '{\"max_queue_limit\":0}', 'Updated queue configuration for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:29:31'),
(556, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 1, '{\"break_end\":\"12:20\"}', '{\"break_end\":\"13:00\"}', 'Updated office schedule for \"Registrar\"', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:29:42'),
(557, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:29:45'),
(558, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:31:05'),
(559, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:31:09'),
(560, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:34:06'),
(561, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:38:25'),
(562, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:38:27'),
(563, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:38:41'),
(564, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.94.215', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 04:39:29'),
(565, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 01:49:24'),
(566, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.95.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 01:49:28'),
(567, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.95.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 01:49:33'),
(568, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:35:45'),
(569, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:46:11'),
(570, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:46:22'),
(571, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:53:15'),
(572, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:53:25'),
(573, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.92.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 06:53:28'),
(574, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-08-30 02:13:45'),
(575, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-08-30 02:13:55'),
(576, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:27:21'),
(577, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:27:50'),
(578, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:34:22'),
(579, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:34:25'),
(580, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:47:41'),
(581, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:47:44'),
(582, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.93.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 01:47:48'),
(583, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 05:59:47'),
(584, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 05:59:56'),
(585, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 06:00:32'),
(586, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 06:00:43'),
(587, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 06:01:11'),
(588, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 06:01:31'),
(589, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '175.158.203.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 06:01:58'),
(590, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:10:13'),
(591, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:10:23'),
(592, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:10:32'),
(593, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:16:00'),
(594, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:16:06'),
(595, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:16:16'),
(596, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:19:59'),
(597, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:20:12'),
(598, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:20:22'),
(599, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:20:43'),
(600, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:21:00'),
(601, 1, 'STAFF_EDIT', 'USER', 6, '{\"office\":\"Admissions\"}', '{\"office\":\"Accounting\"}', 'Updated staff member: Lizelda Marie', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:21:28'),
(602, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:21:32'),
(603, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '175.158.203.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:21:49'),
(604, 6, 'LOGIN', 'user', 6, NULL, NULL, 'User logged in successfully', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:25:05'),
(605, 6, 'SERVICE_ADD', 'SERVICE', 22, NULL, '{\"name\":\"Others\",\"category\":\"simple\",\"description\":\"Others\",\"office\":\"Accounting\",\"windows\":[]}', 'Added new service \"Others\" for Accounting', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:25:36'),
(606, 6, 'LOGOUT', 'user', 6, NULL, NULL, 'User logged out', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 07:34:03'),
(607, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 23:51:40'),
(608, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 23:51:55'),
(609, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 23:56:02'),
(610, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 23:56:10'),
(611, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-01 23:56:45'),
(612, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 00:23:26'),
(613, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 00:23:37'),
(614, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 01:48:28'),
(615, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 05:53:22'),
(616, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 09:28:31'),
(617, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 11:07:43'),
(618, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 12:13:21'),
(619, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"17:00\"}', '{\"close_time\":\"21:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 12:13:30'),
(620, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 12:17:15'),
(621, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 12:17:18'),
(622, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:23:09'),
(623, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:23:13'),
(624, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 04:57:26'),
(625, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 05:22:30'),
(626, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '136.158.10.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 09:17:50'),
(627, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '136.158.10.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 09:18:48'),
(628, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:21:53');
INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(629, 1, 'SETTINGS_SCHEDULE_UPDATE', 'OFFICE', 2, '{\"close_time\":\"21:00\"}', '{\"close_time\":\"17:00\"}', 'Updated office schedule for \"Accounting\"', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:21:59'),
(630, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":true}', '{\"kiosk_enabled\":false}', 'Updated kiosk settings', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:22:03'),
(631, 1, 'SETTINGS_QUEUE_UPDATE', 'OFFICE', 2, '{\"queue_prefix\":\"ACC\"}', '{\"queue_prefix\":\"\"}', 'Updated queue configuration for \"Accounting\"', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:22:29'),
(632, 1, 'SETTINGS_QUEUE_UPDATE', 'OFFICE', 2, '{\"queue_prefix\":\"\"}', '{\"queue_prefix\":\"ACC\"}', 'Updated queue configuration for \"Accounting\"', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:22:35'),
(633, 1, 'STAFF_EDIT', 'USER', 6, '{\"office\":\"Accounting\"}', '{\"office\":\"Admissions\"}', 'Updated staff member: Lizelda Marie', '110.93.95.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:23:06'),
(634, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '203.160.161.70', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-10 05:46:04'),
(635, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '203.160.161.70', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-10 05:46:22'),
(636, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '203.160.190.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 07:20:14'),
(637, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:07:53'),
(638, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:16:07'),
(639, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:16:10'),
(640, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.137.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', '2026-09-11 06:32:50'),
(641, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:21:22'),
(642, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '110.93.93.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:27:54');

-- --------------------------------------------------------

--
-- Table structure for table `kiosk_settings`
--

CREATE TABLE `kiosk_settings` (
  `id` int(11) NOT NULL,
  `kiosk_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `idle_timeout` smallint(6) NOT NULL DEFAULT 60,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kiosk_settings`
--

INSERT INTO `kiosk_settings` (`id`, `kiosk_enabled`, `idle_timeout`, `updated_at`) VALUES
(1, 1, 60, '2026-09-10 05:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `window_count` int(11) DEFAULT 1,
  `queue_mode` enum('unified','service-based','level-based') DEFAULT 'unified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `open_time` time NOT NULL DEFAULT '08:00:00',
  `close_time` time NOT NULL DEFAULT '17:00:00',
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `queue_cutoff` time DEFAULT NULL,
  `queue_prefix` varchar(10) NOT NULL DEFAULT '',
  `priority_prefix` varchar(10) DEFAULT NULL,
  `queue_start_num` smallint(4) NOT NULL DEFAULT 1,
  `daily_reset` tinyint(1) NOT NULL DEFAULT 1,
  `max_queue_limit` smallint(4) NOT NULL DEFAULT 0,
  `auto_close` tinyint(1) NOT NULL DEFAULT 1,
  `service_hours` varchar(100) DEFAULT NULL,
  `location_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `code`, `description`, `window_count`, `queue_mode`, `created_at`, `open_time`, `close_time`, `break_start`, `break_end`, `queue_cutoff`, `queue_prefix`, `priority_prefix`, `queue_start_num`, `daily_reset`, `max_queue_limit`, `auto_close`, `service_hours`, `location_info`) VALUES
(1, 'Registrar', 'REG', 'Registrar Office', 9, 'unified', '2026-03-25 08:03:37', '07:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'REG', NULL, 1, 1, 0, 1, NULL, NULL),
(2, 'Accounting', 'ACC', 'Accounting Office', 10, 'service-based', '2026-03-25 08:03:37', '07:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'ACC', NULL, 1, 1, 0, 1, NULL, NULL),
(3, 'Admissions', 'ADM', 'Admissions Office', 5, 'unified', '2026-03-25 08:03:37', '07:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'ADM', NULL, 1, 1, 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `group_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `key`, `label`, `group_name`) VALUES
(2, 'view_staff', 'View Staff List', 'Staff'),
(4, 'view_services', 'View Services', 'Services'),
(5, 'view_own_metrics', 'View Own Performance Metrics', 'Reports'),
(6, 'manage_queue', 'Manage Queue Windows', 'Queue'),
(7, 'view_queue', 'View Queue', 'Queue'),
(8, 'manage_settings', 'System Settings', 'Settings'),
(10, 'view_reports', 'View Full Reports & Analytics', 'Reports'),
(11, 'view_reports_office', 'View Office Reports', 'Reports'),
(12, 'add_staff', 'Add Staff', 'Staff'),
(13, 'edit_staff', 'Edit Staff', 'Staff'),
(15, 'add_services', 'Add Services', 'Services'),
(16, 'edit_services', 'Edit Services', 'Services');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`) VALUES
(1, 'admin', '2026-05-02 02:07:01'),
(2, 'Staff', '2026-05-02 02:07:01'),
(3, 'Department Head', '2026-05-02 02:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 2),
(2, 4),
(3, 4),
(2, 5),
(2, 6),
(2, 7),
(3, 11),
(3, 13),
(3, 15),
(3, 16);

-- --------------------------------------------------------

--
-- Table structure for table `service_windows`
--

CREATE TABLE `service_windows` (
  `service_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `window_number` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_windows`
--

INSERT INTO `service_windows` (`service_id`, `office_id`, `window_number`) VALUES
(3, 2, 1),
(4, 2, 1),
(21, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_feedback`
--

CREATE TABLE `staff_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `office_id` int(10) UNSIGNED DEFAULT NULL,
  `ticket_id` int(10) UNSIGNED DEFAULT NULL,
  `ticket_number` varchar(50) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_feedback`
--

INSERT INTO `staff_feedback` (`id`, `staff_id`, `office_id`, `ticket_id`, `ticket_number`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 2, 132, 'ACC-007', 5, '', '2026-09-02 17:53:47');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `visitor_name` varchar(100) DEFAULT NULL,
  `tracking_token` char(64) DEFAULT NULL,
  `office_id` int(11) NOT NULL,
  `is_priority` tinyint(1) NOT NULL DEFAULT 0,
  `transaction_type` varchar(50) DEFAULT NULL,
  `student_level` enum('shs','college') DEFAULT NULL,
  `window_id` int(11) DEFAULT NULL,
  `status` enum('waiting','serving','completed','skipped','on-hold','cancelled') NOT NULL DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `original_created_at` datetime DEFAULT NULL,
  `queue_order_at` datetime(6) DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `on_hold_at` timestamp NULL DEFAULT NULL,
  `served_by` int(11) DEFAULT NULL,
  `time_spent` int(11) DEFAULT 0,
  `counter_window` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ticket_events` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `event` varchar(50) NOT NULL,
  `from_status` varchar(20) DEFAULT NULL,
  `to_status` varchar(20) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `office_id` int(11) NOT NULL,
  `window_id` int(11) DEFAULT NULL,
  `meta` JSON NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_events_ticket_created` (`ticket_id`,`created_at`),
  KEY `idx_ticket_events_office_created` (`office_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `tracking_token`, `office_id`, `is_priority`, `transaction_type`, `window_id`, `status`, `created_at`, `served_at`, `completed_at`, `on_hold_at`, `served_by`, `time_spent`, `counter_window`) VALUES
(20, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', NULL, 'completed', '2026-04-18 06:36:52', '2026-04-18 06:39:42', '2026-04-18 06:40:31', NULL, 2, 0, NULL),
(21, 'ACC-002', NULL, 2, 0, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 06:37:47', '2026-04-18 07:06:14', '2026-04-18 07:14:14', '2026-04-18 06:41:08', 2, 0, NULL),
(22, 'ACC-003', NULL, 2, 0, 'Payment Issue', NULL, 'completed', '2026-04-18 06:38:14', '2026-04-18 06:48:50', '2026-04-18 06:48:54', '2026-04-18 06:48:33', 2, 0, NULL),
(23, 'ACC-004', NULL, 2, 0, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:38:22', '2026-04-18 06:50:43', '2026-04-18 06:54:51', NULL, 2, 0, NULL),
(24, 'ACC-005', NULL, 2, 0, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:57:22', '2026-04-18 06:58:03', '2026-04-18 06:58:13', NULL, 2, 0, NULL),
(25, 'ACC-006', NULL, 2, 0, 'Promissory Note', NULL, 'completed', '2026-04-18 06:59:10', '2026-04-18 06:59:19', '2026-04-18 06:59:26', NULL, 2, 0, NULL),
(26, 'ACC-007', NULL, 2, 0, 'Payment Issue', NULL, 'skipped', '2026-04-18 07:02:49', '2026-04-18 07:04:33', NULL, '2026-04-18 07:02:56', 2, 0, NULL),
(27, 'ACC-008', NULL, 2, 0, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:04:59', '2026-04-18 07:05:03', '2026-04-18 07:05:07', NULL, 2, 0, NULL),
(28, 'ACC-009', NULL, 2, 0, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:11:22', '2026-04-18 07:15:31', '2026-04-18 07:15:49', '2026-04-18 07:15:28', 2, 0, NULL),
(30, 'ACC-001', NULL, 2, 0, 'Tuition Fee', NULL, 'completed', '2026-04-20 01:09:27', '2026-04-20 01:11:36', '2026-04-20 01:12:10', NULL, 2, 0, NULL),
(31, 'ACC-002', NULL, 2, 0, 'Balance Inquiry', NULL, 'completed', '2026-04-20 01:09:50', '2026-04-20 01:14:43', '2026-04-20 01:15:11', NULL, 2, 0, NULL),
(32, 'ACC-003', NULL, 2, 0, 'Online Payment Validation', NULL, 'completed', '2026-04-20 01:09:59', '2026-04-20 01:13:41', '2026-04-20 01:14:40', '2026-04-20 01:12:56', 2, 0, NULL),
(33, 'ACC-004', NULL, 2, 0, 'Payment Issue', NULL, 'completed', '2026-04-20 01:10:04', '2026-04-20 01:12:58', '2026-04-20 01:13:40', NULL, 2, 0, NULL),
(34, 'ADM-001', NULL, 3, 0, NULL, NULL, 'completed', '2026-04-20 01:17:02', '2026-04-20 01:17:13', '2026-04-20 01:17:33', NULL, 3, 0, NULL),
(35, 'ADM-002', NULL, 3, 0, NULL, NULL, 'completed', '2026-04-20 01:17:05', '2026-04-20 01:17:33', '2026-04-20 01:17:34', NULL, 3, 0, NULL),
(36, 'ADM-003', NULL, 3, 0, NULL, NULL, 'completed', '2026-04-20 01:17:06', '2026-04-20 01:17:35', '2026-04-20 01:17:35', NULL, 3, 0, NULL),
(37, 'REG-001', NULL, 1, 0, NULL, NULL, 'completed', '2026-04-20 01:18:54', '2026-04-20 01:19:20', '2026-04-20 01:19:21', NULL, 4, 0, NULL),
(38, 'REG-002', NULL, 1, 0, NULL, NULL, 'completed', '2026-04-20 01:18:56', '2026-04-20 01:19:21', '2026-04-20 01:19:23', NULL, 4, 0, NULL),
(39, 'REG-003', NULL, 1, 0, NULL, NULL, 'skipped', '2026-04-20 01:18:58', '2026-04-20 01:19:23', NULL, NULL, 4, 0, NULL),
(40, 'REG-004', NULL, 1, 0, NULL, NULL, 'completed', '2026-04-20 01:19:00', '2026-04-20 01:19:24', '2026-04-20 01:19:25', NULL, 4, 0, NULL),
(41, 'REG-005', NULL, 1, 0, NULL, NULL, 'completed', '2026-04-20 01:19:01', '2026-04-20 01:19:25', '2026-04-20 01:19:25', NULL, 4, 0, NULL),
(42, 'ADM-001', NULL, 3, 0, NULL, NULL, 'skipped', '2026-04-22 13:38:28', NULL, NULL, NULL, 2, 0, NULL),
(43, 'ADM-002', NULL, 3, 0, NULL, NULL, 'skipped', '2026-04-22 13:39:53', NULL, NULL, NULL, 2, 0, NULL),
(44, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', NULL, 'skipped', '2026-04-22 14:49:28', NULL, NULL, NULL, 2, 0, NULL),
(45, 'ACC-001', NULL, 2, 0, 'Tuition Fee', NULL, 'completed', '2026-04-25 01:23:25', '2026-04-25 01:26:21', '2026-04-25 01:30:39', NULL, 2, 0, NULL),
(46, 'ACC-002', NULL, 2, 0, 'Tuition Fee', NULL, 'completed', '2026-04-25 01:30:53', '2026-04-25 01:35:40', '2026-04-25 01:35:49', NULL, 2, 0, NULL),
(47, 'ACC-003', NULL, 2, 0, 'Promissory Note', NULL, 'completed', '2026-04-25 01:31:32', '2026-04-25 01:35:50', '2026-04-25 01:35:54', NULL, 2, 0, NULL),
(48, 'ACC-004', NULL, 2, 0, 'Miscellaneous Fee', NULL, 'skipped', '2026-04-25 03:43:14', NULL, NULL, NULL, 2, 0, NULL),
(49, 'ACC-005', NULL, 2, 0, 'Balance Inquiry', NULL, 'skipped', '2026-04-25 03:53:03', NULL, NULL, NULL, 2, 0, NULL),
(50, 'ACC-006', NULL, 2, 0, 'Balance Inquiry', NULL, 'skipped', '2026-04-25 03:53:59', NULL, NULL, NULL, 2, 0, NULL),
(51, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', NULL, 'completed', '2026-05-01 02:25:54', '2026-05-01 02:37:00', '2026-05-01 02:38:03', NULL, 2, 18, NULL),
(52, 'ACC-002', NULL, 2, 0, 'Balance Inquiry', NULL, 'completed', '2026-05-01 02:43:33', '2026-05-01 02:51:10', '2026-05-01 02:52:58', '2026-05-01 02:51:06', 2, 14, NULL),
(53, 'ACC-003', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-01 07:53:53', '2026-05-01 02:51:10', '2026-05-01 02:51:10', NULL, 2, 0, NULL),
(54, 'ACC-001', NULL, 2, 0, 'Tuition Fee', 1, 'skipped', '2026-05-02 08:21:40', NULL, NULL, NULL, 2, 0, NULL),
(55, 'ACC-002', NULL, 2, 0, 'Promissory Note', NULL, 'skipped', '2026-05-02 08:21:55', NULL, NULL, NULL, 2, 0, NULL),
(56, 'ACC-003', NULL, 2, 0, 'Online Payment Validation', NULL, 'skipped', '2026-05-02 08:22:02', NULL, NULL, NULL, 2, 0, NULL),
(57, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-04 00:55:10', '2026-05-04 00:55:20', '2026-05-04 00:55:37', NULL, 2, 0, NULL),
(77, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-13 12:30:35', '2026-05-13 12:31:00', '2026-05-13 12:31:21', NULL, 2, 0, NULL),
(78, 'ACC-002', NULL, 2, 0, 'Miscellaneous Fee', 1, 'completed', '2026-05-13 12:32:16', '2026-05-13 12:35:08', '2026-05-13 12:35:22', '2026-05-13 12:34:32', 2, 42, NULL),
(79, 'ACC-001', NULL, 2, 0, 'Tuition Fee', 1, 'completed', '2026-05-14 01:13:53', '2026-05-14 01:14:52', '2026-05-14 01:16:08', NULL, 2, 0, NULL),
(80, 'ACC-002', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-14 01:55:44', '2026-05-14 01:57:03', '2026-05-14 02:02:37', NULL, 2, 0, NULL),
(81, 'ADM-001', NULL, 3, 0, NULL, NULL, 'waiting', '2026-05-18 05:32:42', NULL, NULL, NULL, NULL, 0, NULL),
(82, 'ACC-001', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 04:35:59', '2026-05-19 05:12:24', '2026-05-19 05:13:21', NULL, 2, 0, NULL),
(83, 'ACC-002', NULL, 2, 0, 'Miscellaneous Fee', 1, 'completed', '2026-05-19 04:36:28', '2026-05-19 05:13:22', '2026-05-19 05:14:24', NULL, 2, 0, NULL),
(84, 'ACC-003', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 04:37:13', '2026-05-19 05:14:25', '2026-05-19 05:15:12', NULL, 2, 0, NULL),
(85, 'ACC-004', NULL, 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 05:49:08', '2026-05-19 05:50:18', '2026-05-19 05:50:25', NULL, 2, 0, NULL),
(86, 'ACC-005', NULL, 2, 0, 'Balance Inquiry', 1, 'waiting', '2026-05-19 05:49:48', NULL, NULL, NULL, NULL, 0, NULL),
(87, 'REG-001', NULL, 1, 0, NULL, NULL, 'waiting', '2026-05-19 05:52:33', NULL, NULL, NULL, NULL, 0, NULL),
(88, 'ACC-001', '3767a6bdeafe505eedbe9d86150ad26a14ab629525e7bb39c68f700b7d53f7b4', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 23:35:31', '2026-05-19 23:36:03', '2026-05-19 23:36:30', NULL, 2, 0, NULL),
(89, 'ACC-002', '9a5ebc1fcd947aceb518b0de3aef42244143c1b055f031eebe93ebcf1c952270', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 23:40:39', '2026-05-19 23:40:59', '2026-05-19 23:43:24', NULL, 2, 0, NULL),
(90, 'ACC-003', '837cce94b34377357f45796899b9fc4f828a16ad6cb20bfc47762ae2965ce21a', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-19 23:49:30', '2026-05-19 23:49:59', '2026-05-19 23:50:42', NULL, 2, 0, NULL),
(91, 'ACC-004', 'bd2ae34b7376e565ef20ba373fe0b58b2c57ed77e352c6673533bd4a08e6e209', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 04:56:58', '2026-05-20 04:57:55', '2026-05-20 04:58:22', NULL, 2, 0, NULL),
(92, 'ACC-005', '6f9a483af20611dd01d6e6b1ac6a6cc5d90db848a99a64898dd90301a2296958', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 04:58:35', '2026-05-20 04:59:23', '2026-05-20 04:59:53', NULL, 2, 0, NULL),
(93, 'ACC-006', '0fe21e4d03d4d86ba77f5002ebf60997c46d7cd3d0f9f7ab77a8a48b066fbdf3', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:45:35', '2026-05-20 05:45:53', '2026-05-20 05:46:11', NULL, 2, 0, NULL),
(94, 'ACC-007', '4ebc47b208c3441e97ceaa6bdc1697ec4823c00aa44a196e3abb4a434e929406', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:46:38', '2026-05-20 05:47:25', '2026-05-20 05:47:42', NULL, 2, 0, NULL),
(95, 'ACC-008', 'abd5353e5e9d763ec590169b20387d3fe3aaafe7f5019f7d9f31d1556f3a8aa3', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:46:48', '2026-05-20 05:47:44', '2026-05-20 05:47:54', NULL, 2, 0, NULL),
(96, 'ACC-009', '4fe1de9262d8d3c414436502924b3d7c2a112d8d23917658091485657e4b0ab5', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:46:52', '2026-05-20 05:47:58', '2026-05-20 05:48:04', NULL, 2, 0, NULL),
(97, 'ACC-010', 'd572605bf22d66fdc7588f4fdd107a4cf82458980e25071d05bf28fd12491bfe', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:46:58', '2026-05-20 05:48:06', '2026-05-20 05:48:10', NULL, 2, 0, NULL),
(98, 'ACC-011', '669dd0d6679de04ea3c476a3303981a31520d775907132f0e534df1e267fa3fc', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-20 05:47:02', '2026-05-20 05:48:17', '2026-05-20 05:48:29', NULL, 2, 0, NULL),
(99, 'ACC-001', '805289a90c79136c7f97f1f32047b06b9b43426e67444ec6b5936397547141ba', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-23 11:39:50', '2026-05-23 11:42:06', '2026-05-23 11:43:06', NULL, 2, 0, NULL),
(100, 'ACC-001', '029f805590cbb7b2496ca200e3a7657a704cecb083885aa3354c0fc1edc4cf67', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-24 23:56:03', '2026-05-24 23:59:07', '2026-05-24 23:59:52', '2026-05-24 23:59:20', 2, 13, NULL),
(101, 'ADM-001', '87e163d7014fde68e4bdccb0b2d8ef142f3777b9b5ac5ff89d3cd43ae8336996', 3, 0, NULL, NULL, 'waiting', '2026-05-24 23:57:11', NULL, NULL, NULL, NULL, 0, NULL),
(102, 'REG-001', 'e0a033dfcc6ce148fe0e15783fb961fcb62ef54fb0b109ebd93b41ef65715937', 1, 0, NULL, NULL, 'completed', '2026-05-24 23:57:54', '2026-05-25 00:15:40', '2026-05-25 00:15:49', NULL, 4, 0, NULL),
(103, 'ACC-002', '2c3de04e03f96003a2644ece58e3fff3764a184d2cc908825657964f39951cfd', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-25 00:02:27', '2026-05-25 00:03:30', '2026-05-25 00:04:20', NULL, 2, 0, NULL),
(104, 'ACC-003', '264f776c92207ff7d3ec2a325635de8126da0a40aac95ad939547b11db68a5be', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-25 00:04:17', '2026-05-25 00:20:27', '2026-05-25 00:20:34', NULL, 2, 0, NULL),
(105, 'ACC-004', '5b0546faca951d14c117af312ee7cf07baf5dee8d6da2976d16e887567fbd61b', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-25 00:04:42', '2026-05-25 00:20:35', '2026-05-25 00:20:37', NULL, 2, 0, NULL),
(106, 'ACC-005', 'e517d48cab88db5904628d012e188b3eaa4017a11473939274c82257b2c8fd93', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-05-25 00:06:42', '2026-05-25 00:20:38', '2026-05-25 00:20:50', NULL, 2, 0, NULL),
(107, 'ADM-002', '03539333a625a5b8528774a1ece06767c67539dedd9e1f82cd0952e29af25932', 3, 0, NULL, NULL, 'waiting', '2026-05-25 00:07:36', NULL, NULL, NULL, NULL, 0, NULL),
(108, 'REG-002', '5ecbad4e24e545e3bc1c848562622d89420511dd1d6069a6d11fe8d613758820', 1, 0, NULL, NULL, 'waiting', '2026-05-25 00:07:54', NULL, NULL, NULL, NULL, 0, NULL),
(109, 'ACC-006', '475d4b8803a4c84064338b4e5b0c6da9d0e62f8066b9b184039efc0ed585e096', 2, 0, 'Promissory Note', NULL, 'waiting', '2026-05-25 00:08:32', NULL, NULL, NULL, NULL, 0, NULL),
(110, 'ACC-007', 'e9506291b6a20d044b80c60213ebdf41e9673c8a7e7d6554731b01629ce6fe4f', 2, 0, 'Promissory Note', NULL, 'waiting', '2026-05-25 00:08:53', NULL, NULL, NULL, NULL, 0, NULL),
(111, 'ACC-008', '6c464b5967223fd0d4f78803eeb819dd8350832705eef64c98c24feb7478ec90', 2, 0, 'Miscellaneous Fee', NULL, 'waiting', '2026-05-25 00:09:13', NULL, NULL, NULL, NULL, 0, NULL),
(112, 'ACC-009', '434624fc5ddb24441f6ed8465643bcd3468bbae3c905a353759c88b07c0dc65d', 2, 0, 'Tuition Fee', 1, 'completed', '2026-05-25 00:18:07', '2026-05-25 00:21:03', '2026-05-25 00:21:24', NULL, 2, 0, NULL),
(113, 'REG-001', '427022d7fb5cb0ff9f9218ebc2813d6e7e521c192669fdeee41a069f8212b70b', 1, 0, NULL, NULL, 'waiting', '2026-07-14 10:59:22', NULL, NULL, NULL, NULL, 0, NULL),
(114, 'REG-002', '84b91a4b3de93580506011f3d7ff29eaa73b735da90b55a928b4bb64e0d23a45', 1, 0, NULL, NULL, 'waiting', '2026-07-14 11:09:57', NULL, NULL, NULL, NULL, 0, NULL),
(115, 'REG-001', 'a22eccb5cb504c72e25cc681b337bb0792fb64b47fa7ffe16d98454ddc666dff', 1, 0, NULL, NULL, 'skipped', '2026-07-15 04:28:40', NULL, NULL, NULL, NULL, 0, NULL),
(116, 'ACC-001', 'cdf3449c5db888ce3fc42e07718ded65ff4964832e6d26d380df4bd747048b8e', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-01 01:28:18', '2026-09-01 01:35:11', '2026-09-01 01:36:14', NULL, 2, 0, NULL),
(117, 'ACC-002', '93f37d527b6cd3ab0946df4415783f18255441fbbf508148e27ebd27ab017ac2', 2, 0, 'System Discrepancy', 1, 'completed', '2026-09-01 01:32:36', '2026-09-01 01:36:16', '2026-09-01 01:37:21', NULL, 2, 0, NULL),
(118, 'ACC-003', 'c8fa28d41b83b7e822ebddda0e9914118fc2bfda40a7621259419f7619e1da20', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-09-01 01:32:41', '2026-09-01 01:37:31', '2026-09-01 01:39:03', NULL, 2, 0, NULL),
(119, 'ACC-004', 'a520d044bc4f22250f8a630be9a42171dc449dbd9c99058c7bad1770ff4bb610', 2, 0, 'Online Payment Validation', 1, 'completed', '2026-09-01 01:32:43', '2026-09-01 01:39:05', '2026-09-01 01:40:43', NULL, 2, 0, NULL),
(120, 'ACC-005', '7fa5bed5ab79eaed6024ef31497ef4d82dd50b6a6eac13648384fcf2071199a4', 2, 0, 'Promissory Note', 1, 'completed', '2026-09-01 01:32:50', '2026-09-01 01:40:44', '2026-09-01 01:42:21', NULL, 2, 0, NULL),
(121, 'ACC-006', '568273d58daa9e643a4db8ebda2eff46355d1baa259a19b5d026142e56246a21', 2, 0, 'Payment Issue', 1, 'completed', '2026-09-01 01:32:54', '2026-09-01 01:42:22', '2026-09-01 01:42:38', NULL, 2, 0, NULL),
(122, 'ACC-007', 'dd15ec6413db3b6c7921c37e35011ef4623062be4a1f264575152fe6262bb604', 2, 0, 'System Discrepancy', 1, 'completed', '2026-09-01 01:36:46', '2026-09-01 01:42:38', '2026-09-01 01:43:07', NULL, 2, 0, NULL),
(123, 'ACC-008', '311fc5059c87417aab7a967c8ca9cd840e752efb361224ebc03e3abd0e65a6bb', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-01 01:47:02', '2026-09-01 01:47:22', '2026-09-01 01:47:23', NULL, 2, 0, NULL),
(124, 'ACC-009', '8156d15424e9ad17376e05decbf147be6be62f8252ae96b597fe0b5d2dcf1a12', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-01 07:16:31', '2026-09-01 07:18:04', '2026-09-01 07:18:10', '2026-09-01 07:17:33', 2, 17, NULL),
(125, 'ACC-010', 'f0d66223305ef4969f207dba97aa5938fedb55f5f17c802282d51bbdefb67ba1', 2, 0, 'Balance Inquiry', 1, 'cancelled', '2026-09-01 07:31:20', NULL, '2026-09-01 07:32:22', NULL, NULL, 0, NULL),
(126, 'ACC-001', '4d13b9b36670083c8632a6a666551b414bddfb9bd3880a08e19a324bfc343d5f', 2, 0, 'Promissory Note', 1, 'completed', '2026-09-02 00:03:55', '2026-09-02 01:57:47', '2026-09-02 09:28:46', '2026-09-02 05:54:13', 2, 15178, NULL),
(127, 'ACC-002', '2ae2051fe49073f1d5326f046dce18a736414b08d1c2b80201d52e1f9c93a9ec', 2, 0, 'Others', 1, 'completed', '2026-09-02 00:04:01', '2026-09-02 09:28:55', '2026-09-02 09:29:09', NULL, 2, 0, NULL),
(128, 'ACC-003', 'd9e075540b24e241f0c8d2be1e43b14a71ffb80b338fae76a6260a30ad33ff61', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-02 00:04:05', '2026-09-02 09:29:22', '2026-09-02 09:29:44', NULL, 2, 0, NULL),
(129, 'ACC-004', '808663da025f61977992f4037f852544e72f060b2e1a995d516d551c10929a76', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-09-02 00:04:08', '2026-09-02 09:35:13', '2026-09-02 09:35:16', NULL, 2, 0, NULL),
(130, 'ACC-005', 'bca1664b01eee7b10779ca4b8cc3effb26ad17d385e5e08231e8214cfb107e64', 2, 0, 'Miscellaneous Fee', 1, 'completed', '2026-09-02 00:04:11', '2026-09-02 09:53:08', '2026-09-02 09:53:09', NULL, 2, 0, NULL),
(131, 'ACC-006', '07227b40dc513d10a672c33821926cd90d95eb444eb4af17d3ea11feec3c8534', 2, 0, 'Payment Issue', 1, 'completed', '2026-09-02 00:04:14', '2026-09-02 09:53:11', '2026-09-02 09:53:22', NULL, 2, 0, NULL),
(132, 'ACC-007', 'b1d3418000b4c868121f378d2c53d2251e8518add2a73aabbd95e4179eecfd6a', 2, 0, 'Balance Inquiry', 1, 'completed', '2026-09-02 00:04:20', '2026-09-02 09:53:23', '2026-09-02 09:53:32', NULL, 2, 0, NULL),
(133, 'ACC-008', 'e86027d2844a3af280f8280dfd0abeaab25aa2d117b7ddc0e8f58dd63b938bc1', 2, 0, 'Balance Inquiry', 1, 'waiting', '2026-09-02 12:14:02', NULL, NULL, NULL, NULL, 0, NULL),
(134, 'ACC-1', 'f0861be299b84d6f978476ca887ae3dbb55ede5e503b5b920ac68ff0ddfc68c8', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-10 05:52:05', '2026-09-10 07:20:34', '2026-09-10 07:21:18', NULL, 2, 0, NULL),
(135, 'ACC-2', 'd59deb5389028f992d1d5da7d654304e77de24aef7c4124ee27f2e23d35bb0fc', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-10 05:53:46', '2026-09-10 07:21:21', '2026-09-10 07:21:26', NULL, 2, 0, NULL),
(136, 'ACC-3', 'd6a28607ae646ec6b6e9d4ba82de3fc327c9c71d6845b2a60995769efbd25f7b', 2, 0, 'Tuition Fee', 1, 'completed', '2026-09-10 07:01:24', '2026-09-10 07:21:40', '2026-09-10 07:22:35', NULL, 2, 0, NULL),
(137, 'ACC-4', '5b3a536a4d4094e0c1b1af6288abda6d65adf936a894acec891d52c32eb920cd', 2, 0, 'System Discrepancy', 1, 'completed', '2026-09-10 07:01:35', '2026-09-10 07:23:25', '2026-09-10 07:23:46', NULL, 2, 0, NULL),
(138, 'ACC-5', '3bafbd7ce81d1a83f32fea3249930e3326bae652185d1c311ed3f46d74afe9af', 2, 0, 'Balance Inquiry', 1, 'waiting', '2026-09-10 07:01:44', NULL, NULL, NULL, NULL, 0, NULL),
(139, 'ACC-6', '0acdb26524536d2ba60145d0f59fdadc7c4cc6053f2da11f182a33a6ce92a898', 2, 0, 'Miscellaneous Fee', NULL, 'waiting', '2026-09-10 07:01:52', NULL, NULL, NULL, NULL, 0, NULL),
(140, 'ACC-1', '81c1896c95995b25af5116f64ffab4ee45a635ba488051f33dfb8814f127c94b', 2, 0, 'Tuition Fee', 1, 'waiting', '2026-09-11 01:49:08', NULL, NULL, NULL, NULL, 0, NULL),
(141, 'ACC-2', '01c79b2bcad1afb6bf3d636fe29d14c32dbfd7a7bcf17f1576613bdca4f8c35e', 2, 0, 'System Discrepancy', NULL, 'waiting', '2026-09-11 08:44:57', NULL, NULL, NULL, NULL, 0, NULL);

UPDATE `tickets`
SET `original_created_at` = `created_at`
WHERE `original_created_at` IS NULL;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('simple','regular','complicated') NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`, `category`, `description`, `is_active`, `is_deleted`, `office_id`, `created_at`, `deleted_at`) VALUES
(1, 'Online Payment Validation', 'simple', 'Online payment validation / push', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(2, 'Miscellaneous Fee', 'simple', 'Miscellaneous fee payments', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(3, 'Tuition Fee', 'regular', 'Tuition fee payments', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(4, 'Balance Inquiry', 'regular', 'Balance inquiries', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(5, 'Promissory Note', 'complicated', 'Promissory notes', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(6, 'Payment Issue', 'complicated', 'Payment issues (e.g., wrong posting in system)', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(7, 'Incomplete Requirements', 'complicated', 'Incomplete requirements', 1, 0, 1, '2026-03-25 08:02:05', NULL),
(8, 'System Discrepancy', 'complicated', 'System discrepancies (AIMS issues)', 1, 0, 2, '2026-03-25 08:02:05', NULL),
(9, 'Enrollment of 2nd Course Students', 'regular', 'Processing of enrollment applications for students pursuing a second degree', 1, 0, 3, '2026-04-25 01:39:50', NULL),
(10, 'Enrollment of Transferees', 'regular', 'Enrollment assistance for students transferring from another school.', 1, 0, 3, '2026-04-25 01:40:40', NULL),
(11, 'Enrollment of New Incoming Grade 11 Students', 'regular', 'Admission and enrollment for new Grade 11 students.', 1, 0, 3, '2026-04-25 01:40:56', NULL),
(12, 'Enrollment of College Freshmen', 'regular', 'Admission and enrollment for first-year college students.', 1, 0, 3, '2026-04-25 01:41:10', NULL),
(13, 'Certificate of Enrollment', 'simple', 'Issuance of proof of current enrollment.', 1, 0, 1, '2026-04-25 01:41:29', NULL),
(14, 'Certificate of Grades', 'simple', 'Issuance of a copy of student grades.', 1, 0, 1, '2026-04-25 01:41:41', NULL),
(15, 'Transcript of Records Request', 'regular', 'Processing request for official transcript of records.', 1, 0, 1, '2026-04-25 01:42:02', NULL),
(16, 'Subject Validation', 'complicated', 'Evaluation of subjects for equivalency or credit.', 1, 0, 1, '2026-04-25 01:42:16', NULL),
(17, 'Diploma Releasing', 'simple', 'Release of prepared diplomas to graduates.', 1, 0, 1, '2026-04-25 01:42:29', NULL),
(18, 'Transfer Credentials', 'regular', 'Processing documents for students transferring out.', 1, 0, 1, '2026-04-25 01:42:43', NULL),
(19, 'Exit Clearance', 'regular', 'Completion of final clearance requirements.', 1, 0, 1, '2026-04-25 01:42:54', NULL),
(20, 'Enrollment Concerns', 'simple', 'Assistance with minor enrollment issues.', 1, 0, 1, '2026-04-25 01:43:04', NULL),
(21, 'Sample Service', 'simple', 'Sample service', 0, 1, 3, '2026-05-17 06:32:29', '2026-05-24 19:45:07'),
(22, 'Others', 'simple', 'Others', 1, 0, 2, '2026-09-01 07:25:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `role` varchar(20) DEFAULT 'admin',
  `role_id` int(11) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `window_number` tinyint(3) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `overflow_mode` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `must_change_password`, `role`, `role_id`, `office_id`, `window_number`, `created_at`, `is_active`, `last_activity`, `password_reset_token`, `password_reset_expires`, `created_by`, `is_deleted`, `overflow_mode`, `deleted_at`) VALUES
(1, 'Angel Loraine Reobilo', 'reobiloangel6@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$WlJwTFZiUlhoTWs4dkVFSg$2xXzhMVRqKcqr5WpRJb2a3z2vTz7rlE+fUTVoUA0eLY', 0, 'admin', 1, NULL, NULL, '2026-03-25 08:45:08', 1, NULL, NULL, NULL, NULL, 0, 0, NULL),
(2, 'Liezel Mae Torres', 'liezelmaetorres@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$YXhxRll3ZmdaTm5DVDA4Yg$VJFkcE3j0F94U7yijmPOZlZy40u1Rk+ykwO7wjDhyHQ', 0, 'staff', 2, 2, 1, '2026-03-25 05:35:57', 1, NULL, NULL, NULL, 1, 0, 1, NULL),
(3, 'Liz Margaret Santiago', 'santiagolizmargaret4@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$eHlxUGk5M2k1bHNuSVduQg$C5b+IZ3QeQWzwS/aEsxR99HUSkD2VGxzYgDzOGjqZ14', 0, 'staff', 2, 3, 1, '2026-04-20 01:15:54', 1, NULL, NULL, NULL, 1, 0, 0, NULL),
(4, 'Iris Remorosa', 'irisremorosa@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$MEdPcUIuNUU5VGtWdHdZWQ$cvMgQFjhW2ibPedFPl91ye66BsSgXIbouMkzIkDK0/4', 0, 'staff', 2, 1, 1, '2026-04-20 01:16:16', 1, NULL, NULL, NULL, 1, 0, 0, NULL),
(5, 'Angel Loraine Reobilo', 'reobiloloraine@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$N25HMHVBQjlMNUVMQXFhbQ$VgCRXHrEM9GxDwa0hapRyuaJrR8/4FlkRsKPpZeQS0o', 0, 'supervisor', 3, 2, NULL, '2026-05-02 02:40:15', 1, NULL, NULL, NULL, 1, 0, 0, NULL),
(6, 'Lizelda Marie', 'ltorres6129ant@student.fatima.edu.ph', '$argon2i$v=19$m=65536,t=4,p=1$cVMzTGZwNUtCWkpGVnk5aA$fRrrf9g2yFFUhwjCsshL0c/j1iVtV9BPMzFWmgdZ/yY', 0, 'Department Head', 3, 3, NULL, '2026-05-03 03:41:15', 1, NULL, NULL, NULL, 1, 0, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `kiosk_settings`
--
ALTER TABLE `kiosk_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `service_windows`
--
ALTER TABLE `service_windows`
  ADD PRIMARY KEY (`service_id`,`window_number`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `staff_feedback`
--
ALTER TABLE `staff_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_feedback_staff` (`staff_id`),
  ADD KEY `idx_staff_feedback_created` (`created_at`),
  ADD KEY `idx_staff_feedback_ticket` (`ticket_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tracking_token` (`tracking_token`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_tickets_office_student_level_status` (`office_id`,`student_level`,`status`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_tickets_served_by` (`served_by`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tt_office` (`office_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=643;

--
-- AUTO_INCREMENT for table `kiosk_settings`
--
ALTER TABLE `kiosk_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff_feedback`
--
ALTER TABLE `staff_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_windows`
--
ALTER TABLE `service_windows`
  ADD CONSTRAINT `service_windows_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `transaction_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_windows_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_served_by` FOREIGN KEY (`served_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD CONSTRAINT `fk_tt_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
