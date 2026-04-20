-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 03:51 AM
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
(106, 1, 'SETTINGS_KIOSK_UPDATE', 'KIOSK', 1, '{\"kiosk_enabled\":false}', '{\"kiosk_enabled\":true}', 'Updated kiosk settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 01:50:34');

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
(1, 1, 60, '2026-04-20 01:50:34');

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
(1, 'Registrar', 'REG', 'Registrar Office', 9, 'unified', '2026-03-25 08:03:37', '08:00:00', '17:00:00', NULL, NULL, NULL, 'REG', 1, 1, 0, 1),
(2, 'Accounting', 'ACC', 'Accounting Office', 10, 'service-based', '2026-03-25 08:03:37', '08:00:00', '17:00:00', NULL, NULL, NULL, 'ACC', 1, 1, 0, 1),
(3, 'Admissions', 'ADM', 'Admissions Office', 5, 'unified', '2026-03-25 08:03:37', '08:00:00', '17:00:00', NULL, NULL, NULL, 'ADM', 1, 1, 0, 1);

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
  `served_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `office_id`, `transaction_type`, `window_id`, `status`, `created_at`, `served_at`, `completed_at`, `on_hold_at`, `served_by`) VALUES
(20, 'ACC-001', 2, 'Balance Inquiry', NULL, 'completed', '2026-04-18 06:36:52', '2026-04-18 06:39:42', '2026-04-18 06:40:31', NULL, 2),
(21, 'ACC-002', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 06:37:47', '2026-04-18 07:06:14', '2026-04-18 07:14:14', '2026-04-18 06:41:08', 2),
(22, 'ACC-003', 2, 'Payment Issue', NULL, 'completed', '2026-04-18 06:38:14', '2026-04-18 06:48:50', '2026-04-18 06:48:54', '2026-04-18 06:48:33', 2),
(23, 'ACC-004', 2, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:38:22', '2026-04-18 06:50:43', '2026-04-18 06:54:51', NULL, 2),
(24, 'ACC-005', 2, 'Tuition Fee', NULL, 'completed', '2026-04-18 06:57:22', '2026-04-18 06:58:03', '2026-04-18 06:58:13', NULL, 2),
(25, 'ACC-006', 2, 'Promissory Note', NULL, 'completed', '2026-04-18 06:59:10', '2026-04-18 06:59:19', '2026-04-18 06:59:26', NULL, 2),
(26, 'ACC-007', 2, 'Payment Issue', NULL, 'skipped', '2026-04-18 07:02:49', '2026-04-18 07:04:33', NULL, '2026-04-18 07:02:56', 2),
(27, 'ACC-008', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:04:59', '2026-04-18 07:05:03', '2026-04-18 07:05:07', NULL, 2),
(28, 'ACC-009', 2, 'Miscellaneous Fee', NULL, 'completed', '2026-04-18 07:11:22', '2026-04-18 07:15:31', '2026-04-18 07:15:49', '2026-04-18 07:15:28', 2),
(30, 'ACC-001', 2, 'Tuition Fee', NULL, 'completed', '2026-04-20 01:09:27', '2026-04-20 01:11:36', '2026-04-20 01:12:10', NULL, 2),
(31, 'ACC-002', 2, 'Balance Inquiry', NULL, 'completed', '2026-04-20 01:09:50', '2026-04-20 01:14:43', '2026-04-20 01:15:11', NULL, 2),
(32, 'ACC-003', 2, 'Online Payment Validation', NULL, 'completed', '2026-04-20 01:09:59', '2026-04-20 01:13:41', '2026-04-20 01:14:40', '2026-04-20 01:12:56', 2),
(33, 'ACC-004', 2, 'Payment Issue', NULL, 'completed', '2026-04-20 01:10:04', '2026-04-20 01:12:58', '2026-04-20 01:13:40', NULL, 2),
(34, 'ADM-001', 3, NULL, NULL, 'completed', '2026-04-20 01:17:02', '2026-04-20 01:17:13', '2026-04-20 01:17:33', NULL, 3),
(35, 'ADM-002', 3, NULL, NULL, 'completed', '2026-04-20 01:17:05', '2026-04-20 01:17:33', '2026-04-20 01:17:34', NULL, 3),
(36, 'ADM-003', 3, NULL, NULL, 'completed', '2026-04-20 01:17:06', '2026-04-20 01:17:35', '2026-04-20 01:17:35', NULL, 3),
(37, 'REG-001', 1, NULL, NULL, 'completed', '2026-04-20 01:18:54', '2026-04-20 01:19:20', '2026-04-20 01:19:21', NULL, 4),
(38, 'REG-002', 1, NULL, NULL, 'completed', '2026-04-20 01:18:56', '2026-04-20 01:19:21', '2026-04-20 01:19:23', NULL, 4),
(39, 'REG-003', 1, NULL, NULL, 'skipped', '2026-04-20 01:18:58', '2026-04-20 01:19:23', NULL, NULL, 4),
(40, 'REG-004', 1, NULL, NULL, 'completed', '2026-04-20 01:19:00', '2026-04-20 01:19:24', '2026-04-20 01:19:25', NULL, 4),
(41, 'REG-005', 1, NULL, NULL, 'completed', '2026-04-20 01:19:01', '2026-04-20 01:19:25', '2026-04-20 01:19:25', NULL, 4);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`, `category`, `description`, `is_active`, `is_deleted`, `office_id`, `created_at`) VALUES
(1, 'Online Payment Validation', 'simple', 'Online payment validation / push', 1, 0, 2, '2026-03-25 08:02:05'),
(2, 'Miscellaneous Fee', 'simple', 'Miscellaneous fee payments', 1, 0, 2, '2026-03-25 08:02:05'),
(3, 'Tuition Fee', 'regular', 'Tuition fee payments', 1, 0, 2, '2026-03-25 08:02:05'),
(4, 'Balance Inquiry', 'regular', 'Balance inquiries', 1, 0, 2, '2026-03-25 08:02:05'),
(5, 'Promissory Note', 'complicated', 'Promissory notes', 1, 0, 2, '2026-03-25 08:02:05'),
(6, 'Payment Issue', 'complicated', 'Payment issues (e.g., wrong posting in system)', 1, 0, 2, '2026-03-25 08:02:05'),
(7, 'Incomplete Requirements', 'complicated', 'Incomplete requirements', 1, 0, 1, '2026-03-25 08:02:05'),
(8, 'System Discrepancy', 'complicated', 'System discrepancies (AIMS issues)', 1, 0, 2, '2026-03-25 08:02:05');

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
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `office_id`, `created_at`, `is_active`, `last_activity`, `password_reset_token`, `password_reset_expires`, `created_by`, `is_deleted`) VALUES
(1, 'Angel Loraine Reobilo', 'reobiloangel6@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$WlJwTFZiUlhoTWs4dkVFSg$2xXzhMVRqKcqr5WpRJb2a3z2vTz7rlE+fUTVoUA0eLY', 'admin', NULL, '2026-03-25 08:45:08', 1, NULL, NULL, NULL, NULL, 0),
(2, 'Liezel Mae Torres', 'liezelmaetorres@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$YXhxRll3ZmdaTm5DVDA4Yg$VJFkcE3j0F94U7yijmPOZlZy40u1Rk+ykwO7wjDhyHQ', 'staff', 2, '2026-03-25 05:35:57', 1, NULL, NULL, NULL, 1, 0),
(3, 'Liz Margaret Santiago', 'santiagolizmargaret4@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$eHlxUGk5M2k1bHNuSVduQg$C5b+IZ3QeQWzwS/aEsxR99HUSkD2VGxzYgDzOGjqZ14', 'staff', 3, '2026-04-20 01:15:54', 1, NULL, NULL, NULL, 1, 0),
(4, 'Iris Remorosa', 'irisremorosa@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$MEdPcUIuNUU5VGtWdHdZWQ$cvMgQFjhW2ibPedFPl91ye66BsSgXIbouMkzIkDK0/4', 'staff', 1, '2026-04-20 01:16:16', 1, NULL, NULL, NULL, 1, 0);

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
  ADD KEY `office_id` (`office_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

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
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
