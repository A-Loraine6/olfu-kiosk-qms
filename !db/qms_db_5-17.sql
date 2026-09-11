-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 10:44 AM
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
-- Database: `qms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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
(383, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 05:45:22'),
(384, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"idle_timeout\":10}', '{\"idle_timeout\":60}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 05:45:29'),
(385, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 06:43:08'),
(386, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-03 07:49:30'),
(387, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 13:51:29'),
(388, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 02:35:50'),
(389, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:11:39'),
(390, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:11:42'),
(391, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:21:30'),
(392, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:21:33'),
(393, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:21:37'),
(394, 3, 'LOGIN', 'user', 3, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:21:40'),
(395, 3, 'LOGOUT', 'user', 3, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:22:16'),
(396, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 03:22:19'),
(397, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 04:27:50'),
(398, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 04:33:43'),
(399, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 04:33:47'),
(400, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:55:44'),
(401, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 01:17:26'),
(402, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 02:54:17'),
(403, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 03:19:02'),
(404, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 03:19:06'),
(405, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 03:21:23'),
(406, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 03:21:27'),
(407, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:43:28'),
(408, 1, 'STAFF_DEACTIVATE', 'USER', 5, '{\"is_active\":true}', '{\"is_active\":false}', 'Deactivated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:46:19'),
(409, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:46:22'),
(410, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:46:30'),
(411, 1, 'STAFF_ACTIVATE', 'USER', 5, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:46:36'),
(412, 1, 'STAFF_DEACTIVATE', 'USER', 5, '{\"is_active\":true}', '{\"is_active\":false}', 'Deactivated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:47:00'),
(413, 1, 'STAFF_ACTIVATE', 'USER', 5, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:47:15'),
(414, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:50:14'),
(415, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:50:19'),
(416, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:50:52'),
(417, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:50:55'),
(418, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:54:49'),
(419, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:54:53'),
(420, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 02:15:19'),
(421, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 02:15:22'),
(422, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:08:19'),
(423, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:10:05'),
(424, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:10:08'),
(425, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:11:09'),
(426, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:11:15'),
(427, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:11:23'),
(428, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:11:25'),
(429, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:14:53'),
(430, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:14:56'),
(431, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:18:08'),
(432, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:25:05'),
(433, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:25:16'),
(434, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:25:22'),
(435, 4, 'LOGIN', 'user', 4, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 03:25:27'),
(436, 4, 'LOGOUT', 'user', 4, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:18:21'),
(437, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:18:30'),
(438, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:45:00');
INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(439, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:54:51'),
(440, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:54:53'),
(441, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored archived staff member: Angel Loraine Reobilo ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 04:58:50'),
(442, 1, 'SERVICE_DELETE', 'SERVICE', 11, NULL, NULL, 'Deleted service \"Enrollment of New Incoming Grade 11 Students\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 05:00:43'),
(443, 1, 'SERVICE_RESTORE', 'SERVICE', 11, NULL, NULL, 'Restored archived service: Enrollment of New Incoming Grade 11 Students', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 05:05:25'),
(444, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 05:15:42'),
(445, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:56:28'),
(446, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored archived staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:56:33'),
(447, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:56:39'),
(448, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored archived staff member: Angel Loraine Reobilo ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:56:50'),
(449, 1, 'SERVICE_ADD', 'SERVICE', 21, NULL, '{\"name\":\"Sample Service\",\"category\":\"simple\",\"description\":\"\",\"office\":\"Admissions\",\"windows\":[2]}', 'Added new service \"Sample Service\" for Admissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:57:09'),
(450, 1, 'SERVICE_DELETE', 'SERVICE', 21, NULL, NULL, 'Deleted service \"Sample Service\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 04:57:34'),
(451, 1, 'SERVICE_RESTORE', 'SERVICE', 21, NULL, NULL, 'Restored archived service: Sample Service', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:09:10'),
(452, 1, 'SERVICE_DELETE', 'SERVICE', 21, NULL, NULL, 'Deleted service \"Sample Service\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:09:18'),
(453, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:11:45'),
(454, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored archived staff member: Angel Loraine Reobilo ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:13:55'),
(455, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:15:40'),
(456, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored archived staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:15:47'),
(457, 1, 'STAFF_ACTIVATE', 'USER', 5, '{\"is_active\":false}', '{\"is_active\":true}', 'Activated staff account: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:16:39'),
(458, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:48:46'),
(459, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:48:49'),
(460, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:48:53'),
(461, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:48:56'),
(462, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:30:04'),
(463, 1, 'LOGOUT', 'user', 1, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:32:57'),
(464, 2, 'LOGIN', 'user', 2, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:33:02'),
(465, 2, 'LOGOUT', 'user', 2, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:33:05'),
(466, 5, 'LOGIN', 'user', 5, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:33:07'),
(467, 5, 'LOGOUT', 'user', 5, NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:33:26'),
(468, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 07:33:37'),
(469, 1, 'STAFF_DELETE', 'USER', 5, NULL, NULL, 'Deleted staff member: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:15:28'),
(470, 1, 'STAFF_RESTORE', 'USER', 5, NULL, NULL, 'Restored staff: Angel Loraine Reobilo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:15:31'),
(471, 1, 'SERVICE_RESTORE', 'SERVICE', 21, NULL, NULL, 'Restored service: Sample Service', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:22:37'),
(472, 1, 'LOGIN', 'user', 1, NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 08:36:17');

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
(1, 1, 60, '2026-05-03 05:45:29');

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
  `queue_mode` enum('unified','service-based') DEFAULT 'unified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `open_time` time NOT NULL DEFAULT '08:00:00',
  `close_time` time NOT NULL DEFAULT '17:00:00',
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `queue_cutoff` time DEFAULT NULL,
  `queue_prefix` varchar(10) NOT NULL DEFAULT '',
  `queue_start_num` smallint(4) NOT NULL DEFAULT 1,
  `daily_reset` tinyint(1) NOT NULL DEFAULT 1,
  `max_queue_limit` smallint(4) NOT NULL DEFAULT 0,
  `auto_close` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `code`, `description`, `window_count`, `queue_mode`, `created_at`, `open_time`, `close_time`, `break_start`, `break_end`, `queue_cutoff`, `queue_prefix`, `queue_start_num`, `daily_reset`, `max_queue_limit`, `auto_close`) VALUES
(1, 'Registrar', 'REG', 'Registrar Office', 9, 'unified', '2026-03-25 08:03:37', '08:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'REG', 1, 1, 0, 1),
(2, 'Accounting', 'ACC', 'Accounting Office', 10, 'service-based', '2026-03-25 08:03:37', '08:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'ACC', 1, 1, 0, 1),
(3, 'Admissions', 'ADM', 'Admissions Office', 5, 'unified', '2026-03-25 08:03:37', '08:00:00', '17:00:00', '12:00:00', '13:00:00', NULL, 'ADM', 1, 1, 0, 1);

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
(3, 'Supervisor', '2026-05-02 02:07:17');

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
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(3, 2),
(3, 4),
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
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `office_id` int(11) NOT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `window_id` int(11) DEFAULT NULL,
  `status` enum('waiting','serving','completed','skipped','on-hold') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `on_hold_at` timestamp NULL DEFAULT NULL,
  `served_by` int(11) DEFAULT NULL,
  `time_spent` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `office_id`, `transaction_type`, `window_id`, `status`, `created_at`, `served_at`, `completed_at`, `on_hold_at`, `served_by`, `time_spent`) VALUES
(20, 'ACC-001', 2, 'Balance Inquiry', NULL, 'completed', '2026-04-18 06:36:52', '2026-04-18 06:39:42', '2026-04-18 06:40:31', NULL, 2, 0),
(21, 'ACC-002', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 06:37:47', '2026-04-18 07:06:14', '2026-04-18 07:14:14', '2026-04-18 06:41:08', 2, 0),
(22, 'ACC-003', 2, 'Payment Issue', NULL, 'completed', '2026-04-18 06:38:14', '2026-04-18 06:48:50', '2026-04-18 06:48:54', '2026-04-18 06:48:33', 2, 0),
(23, 'ACC-004', 2, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:38:22', '2026-04-18 06:50:43', '2026-04-18 06:54:51', NULL, 2, 0),
(24, 'ACC-005', 2, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:57:22', '2026-04-18 06:58:03', '2026-04-18 06:58:13', NULL, 2, 0),
(25, 'ACC-006', 2, 'Promissory Note', NULL, 'completed', '2026-04-18 06:59:10', '2026-04-18 06:59:19', '2026-04-18 06:59:26', NULL, 2, 0),
(26, 'ACC-007', 2, 'Payment Issue', NULL, 'skipped', '2026-04-18 07:02:49', '2026-04-18 07:04:33', NULL, '2026-04-18 07:02:56', 2, 0),
(27, 'ACC-008', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:04:59', '2026-04-18 07:05:03', '2026-04-18 07:05:07', NULL, 2, 0),
(28, 'ACC-009', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:11:22', '2026-04-18 07:15:31', '2026-04-18 07:15:49', '2026-04-18 07:15:28', 2, 0),
(30, 'ACC-001', 2, 'Tuition Fee', NULL, 'completed', '2026-04-20 01:09:27', '2026-04-20 01:11:36', '2026-04-20 01:12:10', NULL, 2, 0),
(31, 'ACC-002', 2, 'Balance Inquiry', NULL, 'completed', '2026-04-20 01:09:50', '2026-04-20 01:14:43', '2026-04-20 01:15:11', NULL, 2, 0),
(32, 'ACC-003', 2, 'Online Payment Validation', NULL, 'completed', '2026-04-20 01:09:59', '2026-04-20 01:13:41', '2026-04-20 01:14:40', '2026-04-20 01:12:56', 2, 0),
(33, 'ACC-004', 2, 'Payment Issue', NULL, 'completed', '2026-04-20 01:10:04', '2026-04-20 01:12:58', '2026-04-20 01:13:40', NULL, 2, 0),
(34, 'ADM-001', 3, NULL, NULL, 'completed', '2026-04-20 01:17:02', '2026-04-20 01:17:13', '2026-04-20 01:17:33', NULL, 3, 0),
(35, 'ADM-002', 3, NULL, NULL, 'completed', '2026-04-20 01:17:05', '2026-04-20 01:17:33', '2026-04-20 01:17:34', NULL, 3, 0),
(36, 'ADM-003', 3, NULL, NULL, 'completed', '2026-04-20 01:17:06', '2026-04-20 01:17:35', '2026-04-20 01:17:35', NULL, 3, 0),
(37, 'REG-001', 1, NULL, NULL, 'completed', '2026-04-20 01:18:54', '2026-04-20 01:19:20', '2026-04-20 01:19:21', NULL, 4, 0),
(38, 'REG-002', 1, NULL, NULL, 'completed', '2026-04-20 01:18:56', '2026-04-20 01:19:21', '2026-04-20 01:19:23', NULL, 4, 0),
(39, 'REG-003', 1, NULL, NULL, 'skipped', '2026-04-20 01:18:58', '2026-04-20 01:19:23', NULL, NULL, 4, 0),
(40, 'REG-004', 1, NULL, NULL, 'completed', '2026-04-20 01:19:00', '2026-04-20 01:19:24', '2026-04-20 01:19:25', NULL, 4, 0),
(41, 'REG-005', 1, NULL, NULL, 'completed', '2026-04-20 01:19:01', '2026-04-20 01:19:25', '2026-04-20 01:19:25', NULL, 4, 0),
(42, 'ADM-001', 3, NULL, NULL, 'skipped', '2026-04-22 13:38:28', NULL, NULL, NULL, 2, 0),
(43, 'ADM-002', 3, NULL, NULL, 'skipped', '2026-04-22 13:39:53', NULL, NULL, NULL, 2, 0),
(44, 'ACC-001', 2, 'Balance Inquiry', NULL, 'skipped', '2026-04-22 14:49:28', NULL, NULL, NULL, 2, 0),
(45, 'ACC-001', 2, 'Tuition Fee', NULL, 'completed', '2026-04-25 01:23:25', '2026-04-25 01:26:21', '2026-04-25 01:30:39', NULL, 2, 0),
(46, 'ACC-002', 2, 'Tuition Fee', NULL, 'completed', '2026-04-25 01:30:53', '2026-04-25 01:35:40', '2026-04-25 01:35:49', NULL, 2, 0),
(47, 'ACC-003', 2, 'Promissory Note', NULL, 'completed', '2026-04-25 01:31:32', '2026-04-25 01:35:50', '2026-04-25 01:35:54', NULL, 2, 0),
(48, 'ACC-004', 2, 'Miscellaneous Fee', NULL, 'skipped', '2026-04-25 03:43:14', NULL, NULL, NULL, 2, 0),
(49, 'ACC-005', 2, 'Balance Inquiry', NULL, 'skipped', '2026-04-25 03:53:03', NULL, NULL, NULL, 2, 0),
(50, 'ACC-006', 2, 'Balance Inquiry', NULL, 'skipped', '2026-04-25 03:53:59', NULL, NULL, NULL, 2, 0),
(51, 'ACC-001', 2, 'Balance Inquiry', NULL, 'completed', '2026-05-01 02:25:54', '2026-05-01 02:37:00', '2026-05-01 02:38:03', NULL, 2, 18),
(52, 'ACC-002', 2, 'Balance Inquiry', NULL, 'completed', '2026-05-01 02:43:33', '2026-05-01 02:51:10', '2026-05-01 02:52:58', '2026-05-01 02:51:06', 2, 14),
(53, 'ACC-003', 2, 'Balance Inquiry', 1, 'completed', '2026-05-01 07:53:53', '2026-05-01 02:51:10', '2026-05-01 02:51:10', NULL, 2, 0),
(54, 'ACC-001', 2, 'Tuition Fee', 1, 'skipped', '2026-05-02 08:21:40', NULL, NULL, NULL, 2, 0),
(55, 'ACC-002', 2, 'Promissory Note', NULL, 'skipped', '2026-05-02 08:21:55', NULL, NULL, NULL, 2, 0),
(56, 'ACC-003', 2, 'Online Payment Validation', NULL, 'skipped', '2026-05-02 08:22:02', NULL, NULL, NULL, 2, 0),
(57, 'ACC-001', 2, 'Balance Inquiry', 1, 'completed', '2026-05-09 03:10:36', '2026-05-09 03:11:58', '2026-05-09 03:15:40', NULL, 2, 0),
(58, 'ACC-002', 2, 'Tuition Fee', 1, 'completed', '2026-05-09 03:10:54', '2026-05-09 03:16:27', '2026-05-09 03:19:31', NULL, 2, 0),
(59, 'ACC-003', 2, 'Payment Issue', NULL, 'skipped', '2026-05-09 03:10:59', NULL, NULL, NULL, 2, 0),
(60, 'ADM-001', 3, NULL, NULL, 'completed', '2026-05-09 03:21:10', '2026-05-09 03:21:45', '2026-05-09 03:21:59', NULL, 3, 0),
(61, 'ACC-001', 2, 'Balance Inquiry', 1, 'completed', '2026-05-14 01:17:06', '2026-05-14 01:17:56', '2026-05-14 01:27:13', NULL, 2, 0),
(62, 'ACC-002', 2, 'Tuition Fee', 1, 'completed', '2026-05-14 01:21:56', '2026-05-14 01:27:17', '2026-05-14 01:28:56', NULL, 2, 0),
(63, 'ACC-003', 2, 'Miscellaneous Fee', 1, 'completed', '2026-05-14 01:21:59', '2026-05-14 01:28:58', '2026-05-14 01:30:11', NULL, 2, 0),
(64, 'ACC-001', 2, 'Balance Inquiry', 1, 'completed', '2026-05-15 01:51:19', '2026-05-15 01:51:55', '2026-05-15 01:54:45', NULL, 2, 0),
(65, 'REG-001', 1, NULL, NULL, 'completed', '2026-05-15 03:25:45', '2026-05-15 03:26:01', '2026-05-15 03:26:03', NULL, 4, 0),
(66, 'REG-002', 1, NULL, NULL, 'completed', '2026-05-15 03:30:33', '2026-05-15 03:31:42', '2026-05-15 03:32:20', NULL, 4, 0),
(67, 'REG-003', 1, NULL, NULL, 'completed', '2026-05-15 03:48:44', '2026-05-15 03:49:02', '2026-05-15 03:53:25', NULL, 4, 0),
(68, 'REG-004', 1, NULL, NULL, 'completed', '2026-05-15 03:48:53', '2026-05-15 03:53:31', '2026-05-15 03:54:33', NULL, 4, 0),
(69, 'REG-005', 1, NULL, NULL, 'completed', '2026-05-15 03:48:56', '2026-05-15 03:54:34', '2026-05-15 03:55:44', NULL, 4, 0),
(70, 'REG-006', 1, NULL, NULL, 'completed', '2026-05-15 03:48:57', '2026-05-15 03:55:45', '2026-05-15 03:59:36', NULL, 4, 0);

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
(11, 'Enrollment of New Incoming Grade 11 Students', 'regular', 'Admission and enrollment for new Grade 11 students.', 0, 0, 3, '2026-04-25 01:40:56', NULL),
(12, 'Enrollment of College Freshmen', 'regular', 'Admission and enrollment for first-year college students.', 1, 0, 3, '2026-04-25 01:41:10', NULL),
(13, 'Certificate of Enrollment', 'simple', 'Issuance of proof of current enrollment.', 1, 0, 1, '2026-04-25 01:41:29', NULL),
(14, 'Certificate of Grades', 'simple', 'Issuance of a copy of student grades.', 1, 0, 1, '2026-04-25 01:41:41', NULL),
(15, 'Transcript of Records Request', 'regular', 'Processing request for official transcript of records.', 1, 0, 1, '2026-04-25 01:42:02', NULL),
(16, 'Subject Validation', 'complicated', 'Evaluation of subjects for equivalency or credit.', 1, 0, 1, '2026-04-25 01:42:16', NULL),
(17, 'Diploma Releasing', 'simple', 'Release of prepared diplomas to graduates.', 1, 0, 1, '2026-04-25 01:42:29', NULL),
(18, 'Transfer Credentials', 'regular', 'Processing documents for students transferring out.', 1, 0, 1, '2026-04-25 01:42:43', NULL),
(19, 'Exit Clearance', 'regular', 'Completion of final clearance requirements.', 1, 0, 1, '2026-04-25 01:42:54', NULL),
(20, 'Enrollment Concerns', 'simple', 'Assistance with minor enrollment issues.', 1, 0, 1, '2026-04-25 01:43:04', NULL),
(21, 'Sample Service', 'simple', '', 0, 0, 3, '2026-05-17 04:57:08', '2026-05-17 13:09:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
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

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `role_id`, `office_id`, `window_number`, `created_at`, `is_active`, `last_activity`, `password_reset_token`, `password_reset_expires`, `created_by`, `is_deleted`, `overflow_mode`, `deleted_at`) VALUES
(1, 'Angel Loraine Reobilo', 'reobiloangel6@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$WlJwTFZiUlhoTWs4dkVFSg$2xXzhMVRqKcqr5WpRJb2a3z2vTz7rlE+fUTVoUA0eLY', 'admin', 1, NULL, NULL, '2026-03-25 08:45:08', 1, NULL, NULL, NULL, NULL, 0, 0, NULL),
(2, 'Liezel Mae Torres', 'liezelmaetorres@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$YXhxRll3ZmdaTm5DVDA4Yg$VJFkcE3j0F94U7yijmPOZlZy40u1Rk+ykwO7wjDhyHQ', 'staff', 2, 2, 1, '2026-03-25 05:35:57', 1, NULL, NULL, NULL, 1, 0, 0, NULL),
(3, 'Liz Margaret Santiago', 'santiagolizmargaret4@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$eHlxUGk5M2k1bHNuSVduQg$C5b+IZ3QeQWzwS/aEsxR99HUSkD2VGxzYgDzOGjqZ14', 'staff', 2, 3, 1, '2026-04-20 01:15:54', 1, NULL, NULL, NULL, 1, 0, 0, NULL),
(4, 'Iris Remorosa', 'irisremorosa@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$MEdPcUIuNUU5VGtWdHdZWQ$cvMgQFjhW2ibPedFPl91ye66BsSgXIbouMkzIkDK0/4', 'staff', 2, 1, 1, '2026-04-20 01:16:16', 1, NULL, NULL, NULL, 1, 0, 1, NULL),
(5, 'Angel Loraine Reobilo', 'reobiloloraine@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$N25HMHVBQjlMNUVMQXFhbQ$VgCRXHrEM9GxDwa0hapRyuaJrR8/4FlkRsKPpZeQS0o', 'supervisor', 3, 2, NULL, '2026-05-02 02:40:15', 0, NULL, NULL, NULL, 1, 0, 0, '2026-05-17 16:15:28'),
(6, 'Lizelda Marie', 'ltorres6129ant@student.fatima.edu.ph', '$argon2i$v=19$m=65536,t=4,p=1$cVMzTGZwNUtCWkpGVnk5aA$fRrrf9g2yFFUhwjCsshL0c/j1iVtV9BPMzFWmgdZ/yY', 'Supervisor', 3, 3, NULL, '2026-05-03 03:41:15', 1, NULL, NULL, NULL, 1, 0, 0, NULL);

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
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_office_id` (`office_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=473;

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
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
