-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 07:55 AM
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
(1, 1, 'STAFF_DELETE', 'USER', 2, '{\"id\":2,\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office_id\":2,\"office\":\"Accounting\",\"is_active\":1,\"created_at\":\"2026-03-25 13:35:57\",\"created_by\":1}', NULL, '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 06:36:52'),
(2, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"role\":null,\"office\":\"Accounting\"}', '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.coms\",\"role\":\"staff\",\"office\":\"Accounting\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 06:37:50'),
(3, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.coms\",\"office\":\"Accounting\"}', '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office\":\"Accounting\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 06:39:11'),
(4, 1, 'PASSWORD_RESET', 'USER', 2, NULL, NULL, '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 06:42:50'),
(5, 1, 'STAFF_DEACTIVATE', 'USER', 2, NULL, NULL, '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 06:44:37'),
(6, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office\":\"Accounting\"}', '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office\":\"Admissions\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 02:55:13'),
(7, 1, 'STAFF_EDIT', 'USER', 2, '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office\":\"Admissions\"}', '{\"full_name\":\"Liezel Mae Torres\",\"email\":\"liezelmaetorres@gmail.com\",\"office\":\"Accounting\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 02:55:21'),
(8, 1, 'STAFF_ACTIVATE', 'USER', 2, NULL, NULL, '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 02:55:24'),
(9, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 2, '{\"queue_mode\":\"service-based\"}', '{\"queue_mode\":\"unified\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 23:47:34'),
(10, 1, 'OFFICE_QUEUE_MODE_TOGGLE', 'OFFICE', 2, '{\"queue_mode\":\"unified\"}', '{\"queue_mode\":\"service-based\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 23:47:36'),
(11, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquiry\",\"category\":\"regular\",\"description\":\"Balance inquiries\",\"office\":\"Accounting\"}', '{\"name\":\"Balance Inquiry\",\"category\":\"regular\",\"description\":\"Balance inquiries\",\"office\":\"Admissions\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 23:49:50'),
(12, 1, 'SERVICE_EDIT', 'SERVICE', 4, '{\"name\":\"Balance Inquiry\",\"category\":\"regular\",\"description\":\"Balance inquiries\",\"office\":\"Admissions\"}', '{\"name\":\"Balance Inquiry\",\"category\":\"regular\",\"description\":\"Balance inquiries\",\"office\":\"Accounting\"}', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 23:50:20');

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
(1, 1, 60, '2026-04-01 05:13:19');

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
  `student_id` varchar(50) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `window_id` int(11) DEFAULT NULL,
  `status` enum('waiting','serving','completed','skipped') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 'Incomplete Requirements', 'complicated', 'Incomplete requirements', 1, 0, 2, '2026-03-25 08:02:05'),
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
(2, 'Liezel Mae Torres', 'liezelmaetorres@gmail.com', '$argon2i$v=19$m=65536,t=4,p=1$d2Vwa05va2NHYzQ0dlhBNg$xiu80MYOMAU0PECsTSqf6+bpUGFG88Y2QdKnZE4QyzA', 'staff', 2, '2026-03-25 05:35:57', 1, NULL, NULL, NULL, 1, 0);

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
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
