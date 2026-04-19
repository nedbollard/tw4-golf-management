-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Apr 19, 2026 at 12:31 AM
-- Server version: 8.0.45
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `TW4_base`
--

-- --------------------------------------------------------

--
-- Table structure for table `application_log`
--

CREATE TABLE `application_log` (
  `row_id` int NOT NULL,
  `timestamp` datetime NOT NULL,
  `level` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `context` json DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `row_id` int NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int DEFAULT NULL,
  `action` enum('create','update','delete','login','logout') NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config_application`
--

CREATE TABLE `config_application` (
  `row_id` int NOT NULL,
  `config_name` varchar(100) NOT NULL,
  `config_value_string` text,
  `config_value_int` int DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `config_type` enum('string','int') NOT NULL DEFAULT 'string'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `config_application`
--

INSERT INTO `config_application` (`row_id`, `config_name`, `config_value_string`, `config_value_int`, `updated_by`, `updated_ts`, `config_type`) VALUES
(1, 'team_haggle_state', 'F', 0, 'admin', '2026-03-18 09:15:47', 'string'),
(2, 'config_status', 'ready', NULL, NULL, '2026-03-18 09:15:47', 'string'),
(3, 'club_name', 'TW4 Golf Club', NULL, 'admin', '2026-03-18 09:15:47', 'string'),
(4, 'competition_name', 'Twilight', NULL, 'admin', '2026-03-18 09:15:47', 'string'),
(5, 'season_year', '25_26', NULL, 'admin', '2026-03-18 09:15:47', 'string'),
(6, 'handicap_system', 'modern', NULL, 'admin', '2026-03-18 09:15:47', 'string'),
(7, 'max_handicap', '54', NULL, 'admin', '2026-03-18 09:15:47', 'string');

-- --------------------------------------------------------

--
-- Table structure for table `course_club`
--

CREATE TABLE `course_club` (
  `row_id` int NOT NULL,
  `name_club` varchar(16) NOT NULL,
  `gender` char(1) NOT NULL,
  `number_hole` int NOT NULL,
  `name_hole` varchar(24) NOT NULL,
  `par` int NOT NULL,
  `stroke` int NOT NULL,
  `updated_by` varchar(32) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `course_club`
--

INSERT INTO `course_club` (`row_id`, `name_club`, `gender`, `number_hole`, `name_hole`, `par`, `stroke`, `updated_by`, `updated_ts`) VALUES
(55, 'ovgc', 'M', 1, 'a', 4, 7, 'admin', '2026-04-17 20:27:14'),
(56, 'ovgc', 'M', 2, 'b', 4, 3, 'admin', '2026-04-17 20:27:14'),
(57, 'ovgc', 'M', 3, 'c', 3, 13, 'admin', '2026-04-17 20:27:14'),
(58, 'ovgc', 'M', 4, 'd', 3, 9, 'admin', '2026-04-17 20:27:14'),
(59, 'ovgc', 'M', 5, 'e', 5, 5, 'admin', '2026-04-17 20:27:14'),
(60, 'ovgc', 'M', 6, 'f', 4, 1, 'admin', '2026-04-17 20:27:14'),
(61, 'ovgc', 'M', 7, 'g', 3, 11, 'admin', '2026-04-17 20:27:14'),
(62, 'ovgc', 'M', 8, 'h', 4, 17, 'admin', '2026-04-17 20:27:14'),
(63, 'ovgc', 'M', 9, 'i', 3, 15, 'admin', '2026-04-17 20:27:14'),
(64, 'ovgc', 'M', 10, 'j', 4, 8, 'admin', '2026-04-17 20:27:14'),
(65, 'ovgc', 'M', 11, 'k', 4, 4, 'admin', '2026-04-17 20:27:14'),
(66, 'ovgc', 'M', 12, 'l', 3, 16, 'admin', '2026-04-17 20:27:14'),
(67, 'ovgc', 'M', 13, 'm', 3, 12, 'admin', '2026-04-17 20:27:14'),
(68, 'ovgc', 'M', 14, 'n', 5, 6, 'admin', '2026-04-17 20:27:14'),
(69, 'ovgc', 'M', 15, 'o', 4, 2, 'admin', '2026-04-17 20:27:14'),
(70, 'ovgc', 'M', 16, 'p', 3, 10, 'admin', '2026-04-17 20:27:14'),
(71, 'ovgc', 'M', 17, 'q', 4, 18, 'admin', '2026-04-17 20:27:14'),
(72, 'ovgc', 'M', 18, 'r', 3, 14, 'admin', '2026-04-17 20:27:14'),
(74, 'ovgc', 'F', 1, 'aa', 4, 7, 'admin', '2026-04-17 20:49:13'),
(75, 'ovgc', 'F', 2, 'bb', 4, 3, 'admin', '2026-04-17 20:49:13'),
(76, 'ovgc', 'F', 3, 'cc', 3, 13, 'admin', '2026-04-17 20:49:13'),
(77, 'ovgc', 'F', 4, 'dd', 3, 9, 'admin', '2026-04-17 20:49:13'),
(78, 'ovgc', 'F', 5, 'ee', 5, 5, 'admin', '2026-04-17 20:49:14'),
(79, 'ovgc', 'F', 6, 'ff', 4, 1, 'admin', '2026-04-17 20:49:14'),
(80, 'ovgc', 'F', 7, 'gg', 3, 11, 'admin', '2026-04-17 20:49:14'),
(81, 'ovgc', 'F', 8, 'hh', 4, 17, 'admin', '2026-04-17 20:49:14'),
(82, 'ovgc', 'F', 9, 'ii', 3, 15, 'admin', '2026-04-17 20:49:14'),
(83, 'ovgc', 'F', 10, 'jj', 4, 8, 'admin', '2026-04-17 20:49:14'),
(84, 'ovgc', 'F', 11, 'kk', 4, 4, 'admin', '2026-04-17 20:49:14'),
(85, 'ovgc', 'F', 12, 'll', 3, 16, 'admin', '2026-04-17 20:49:14'),
(86, 'ovgc', 'F', 13, 'mm', 3, 12, 'admin', '2026-04-17 20:49:14'),
(87, 'ovgc', 'F', 14, 'nn', 5, 6, 'admin', '2026-04-17 20:49:14'),
(88, 'ovgc', 'F', 15, 'oo', 4, 2, 'admin', '2026-04-17 20:49:14'),
(89, 'ovgc', 'F', 16, 'pp', 3, 10, 'admin', '2026-04-17 20:49:14'),
(90, 'ovgc', 'F', 17, 'qq', 4, 18, 'admin', '2026-04-17 20:49:14'),
(91, 'ovgc', 'F', 18, 'rr', 3, 14, 'admin', '2026-04-17 20:49:14');

-- --------------------------------------------------------

--
-- Table structure for table `course_played`
--

CREATE TABLE `course_played` (
  `row_id` int NOT NULL,
  `name_course` varchar(16) NOT NULL,
  `name_club` varchar(16) NOT NULL,
  `ident_eclectic` varchar(16) NOT NULL,
  `updated_by` varchar(32) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `course_played`
--

INSERT INTO `course_played` (`row_id`, `name_course`, `name_club`, `ident_eclectic`, `updated_by`, `updated_ts`) VALUES
(3, 'Whites', 'ovgc', 'twilight', 'admin', '2026-04-18 02:29:36'),
(4, 'Blues', 'ovgc', 'twilight', 'admin', '2026-04-18 02:30:32');

-- --------------------------------------------------------

--
-- Table structure for table `course_played_hole`
--

CREATE TABLE `course_played_hole` (
  `row_id` int NOT NULL,
  `course_played_id` int NOT NULL,
  `number_hole` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT=' ';

--
-- Dumping data for table `course_played_hole`
--

INSERT INTO `course_played_hole` (`row_id`, `course_played_id`, `number_hole`, `updated_by`, `updated_ts`) VALUES
(19, 3, 1, 'admin', '2026-04-18 02:29:36'),
(20, 3, 2, 'admin', '2026-04-18 02:29:36'),
(21, 3, 3, 'admin', '2026-04-18 02:29:36'),
(22, 3, 4, 'admin', '2026-04-18 02:29:36'),
(23, 3, 5, 'admin', '2026-04-18 02:29:36'),
(24, 3, 6, 'admin', '2026-04-18 02:29:36'),
(25, 3, 7, 'admin', '2026-04-18 02:29:36'),
(26, 3, 8, 'admin', '2026-04-18 02:29:36'),
(27, 3, 9, 'admin', '2026-04-18 02:29:36'),
(28, 4, 10, 'admin', '2026-04-18 02:30:32'),
(29, 4, 11, 'admin', '2026-04-18 02:30:32'),
(30, 4, 12, 'admin', '2026-04-18 02:30:32'),
(31, 4, 13, 'admin', '2026-04-18 02:30:32'),
(32, 4, 14, 'admin', '2026-04-18 02:30:32'),
(33, 4, 15, 'admin', '2026-04-18 02:30:32'),
(34, 4, 16, 'admin', '2026-04-18 02:30:32'),
(35, 4, 17, 'admin', '2026-04-18 02:30:32'),
(36, 4, 18, 'admin', '2026-04-18 02:30:32');

-- --------------------------------------------------------

--
-- Table structure for table `roster`
--

CREATE TABLE `roster` (
  `row_id` int NOT NULL,
  `player_identifier` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `handicap` int DEFAULT '0',
  `date_first_played` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roster`
--

INSERT INTO `roster` (`row_id`, `player_identifier`, `first_name`, `last_name`, `alias`, `gender`, `status`, `handicap`, `date_first_played`, `created_at`, `updated_by`, `updated_at`) VALUES
(1, 'TestU', 'Test', 'User', NULL, 'male', 'active', 0, NULL, '2026-04-19 00:09:05', NULL, '2026-04-19 00:09:05'),
(2, 'TestU1', 'Test', 'User', NULL, 'male', 'active', 0, NULL, '2026-04-19 00:09:37', NULL, '2026-04-19 00:09:37'),
(3, 'TestU2', 'Test', 'User', NULL, 'male', 'active', 0, NULL, '2026-04-19 00:10:19', NULL, '2026-04-19 00:10:19'),
(4, 'TestU3', 'Test', 'User', NULL, 'male', 'active', 0, NULL, '2026-04-19 00:10:59', NULL, '2026-04-19 00:10:59'),
(6, 'JohnH', 'John', 'Henry', 'CakeTin1', 'male', 'active', 18, '2026-04-01', '2026-04-12 20:51:16', 'scorer', '2026-04-12 21:33:18'),
(7, 'BillG', 'Bill', 'Gates', '', 'male', 'active', 36, NULL, '2026-04-12 21:20:44', 'scorer', '2026-04-12 21:20:44'),
(8, 'MaxB', 'Max', 'Bygraves', 'CakeTin', 'male', 'active', 42, NULL, '2026-04-12 21:32:33', 'scorer', '2026-04-12 21:40:23'),
(9, 'CaitlynO', 'Caitlyn', 'O\'Connor', 'CateC', 'female', 'active', 12, NULL, '2026-04-12 21:41:26', 'scorer', '2026-04-12 21:41:26'),
(10, 'FiliLT', 'Fili', 'Lu\'Iana', 'Fili', 'male', 'active', 10, NULL, '2026-04-12 21:42:16', 'scorer', '2026-04-12 21:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `row_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','scorer') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`row_id`, `username`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `created_at`, `updated_ts`, `last_login`) VALUES
(1, 'admin', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'System', 'Administrator', 'admin', 1, '2026-04-19 00:08:54', '2026-04-19 00:08:54', NULL),
(2, 'testuser', '$2y$10$fSC70lEcc3ecMiWwFsjPvuLRinPIk70Z2Qx2VeNcOEVqGAFLWGqx2', 'Test', 'User', 'admin', 1, '2026-04-19 00:09:04', '2026-04-19 00:09:04', NULL),
(3, 'jimg', '$2y$10$rY1E1HzlmlX5jfoS03fOkenJKrMfSmrl5EBRoDB9R8Wk9J9X4kLpe', 'James', 'Gifkins', 'scorer', 1, '2026-03-19 07:47:31', '2026-03-19 07:48:01', NULL),
(5, 'scorer', '$2y$10$3jkRx4PWv6.aHjXZHffl4.yTrvB8rpaJYCAL6Frk5oqJKvDAG2GGq', 'Beeny', 'Goodman', 'scorer', 1, '2026-04-12 21:01:14', '2026-04-12 21:01:14', NULL),
(6, 'Scorer1', '$2y$10$rwCS0eYETNaJWQpG5q32Tebl5bz0WzBVB3tbPRHSSw8ttDeHkqlrm', 'Morkel', 'Mcdonald', 'scorer', 1, '2026-04-13 22:13:45', '2026-04-13 22:23:25', NULL),
(9, 'logouttest', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'Logout', 'Test', 'admin', 1, '2026-04-13 23:26:38', '2026-04-13 23:26:38', NULL),
(10, 'testlogout', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'Test', 'Logout', 'admin', 1, '2026-04-14 00:53:10', '2026-04-14 00:53:10', NULL),
(11, 'testlogout2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'Logout2', 'admin', 1, '2026-04-14 00:53:41', '2026-04-14 00:53:41', NULL),
(12, 'logintester', '$2y$10$q9i/h5Q8M9IGuUiFl/1LBOosgtxVW0QsQCGW9Jq/Og7eBmqVgttJm', 'Login', 'Tester', 'scorer', 1, '2026-04-14 01:05:53', '2026-04-14 01:05:53', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application_log`
--
ALTER TABLE `application_log`
  ADD PRIMARY KEY (`row_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`row_id`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `config_application`
--
ALTER TABLE `config_application`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `config_name` (`config_name`),
  ADD KEY `idx_config_name` (`config_name`);

--
-- Indexes for table `course_club`
--
ALTER TABLE `course_club`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `unique_hole` (`name_club`,`gender`,`number_hole`);

--
-- Indexes for table `course_played`
--
ALTER TABLE `course_played`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `unique_course_played` (`name_club`,`name_course`);

--
-- Indexes for table `course_played_hole`
--
ALTER TABLE `course_played_hole`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `unique_course_played_number_hole` (`course_played_id`,`number_hole`);

--
-- Indexes for table `roster`
--
ALTER TABLE `roster`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `player_identifier` (`player_identifier`),
  ADD UNIQUE KEY `alias` (`alias`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`row_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application_log`
--
ALTER TABLE `application_log`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=427;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `config_application`
--
ALTER TABLE `config_application`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course_club`
--
ALTER TABLE `course_club`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `course_played`
--
ALTER TABLE `course_played`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `course_played_hole`
--
ALTER TABLE `course_played_hole`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `roster`
--
ALTER TABLE `roster`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `row_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`row_id`);

--
-- Constraints for table `course_played_hole`
--
ALTER TABLE `course_played_hole`
  ADD CONSTRAINT `fk_course_played_hole_course_played` FOREIGN KEY (`course_played_id`) REFERENCES `course_played` (`row_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
