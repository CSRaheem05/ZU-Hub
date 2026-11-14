-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2026 at 01:49 PM
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
-- Database: `zu_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `college_code` varchar(20) NOT NULL,
  `college_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` varchar(30) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `course_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_members`
--

CREATE TABLE `course_members` (
  `user_id` varchar(50) NOT NULL,
  `offering_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `course_members`
--
DELIMITER $$
CREATE TRIGGER `trg_members_after_delete` AFTER DELETE ON `course_members` FOR EACH ROW BEGIN
  UPDATE `Course_Offerings` SET `students_count` = GREATEST(`students_count` - 1, 0)
  WHERE `offering_id` = OLD.`offering_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_members_after_insert` AFTER INSERT ON `course_members` FOR EACH ROW BEGIN
  UPDATE `Course_Offerings` SET `students_count` = `students_count` + 1
  WHERE `offering_id` = NEW.`offering_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_members_after_update` AFTER UPDATE ON `course_members` FOR EACH ROW BEGIN
  IF OLD.`offering_id` <> NEW.`offering_id` THEN
    UPDATE `Course_Offerings` SET `students_count` = GREATEST(`students_count` - 1, 0)
    WHERE `offering_id` = OLD.`offering_id`;
    UPDATE `Course_Offerings` SET `students_count` = `students_count` + 1
    WHERE `offering_id` = NEW.`offering_id`;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `course_offerings`
--

CREATE TABLE `course_offerings` (
  `offering_id` varchar(50) NOT NULL,
  `course_id` varchar(30) NOT NULL,
  `instructor_id` varchar(50) NOT NULL,
  `students_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_hours` int(10) UNSIGNED DEFAULT NULL,
  `lecture_schedule` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_code` varchar(20) NOT NULL,
  `college_code` varchar(20) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` varchar(50) NOT NULL,
  `offering_id` varchar(50) NOT NULL,
  `lesson_title` varchar(255) NOT NULL,
  `lesson_type` enum('video','link','live') NOT NULL,
  `lesson_order` int(11) NOT NULL DEFAULT 0,
  `lesson_url` text DEFAULT NULL,
  `live_url` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `offering_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `user_id` varchar(50) NOT NULL,
  `lesson_id` varchar(50) NOT NULL,
  `last_position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `user_id` varchar(50) NOT NULL,
  `offering_id` varchar(50) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','instructor','manager') NOT NULL DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
('ADM-001', 'Admin', 'admin@zu.edu.jo', '$2y$12$b5uXjMY0HCDF/Tq9uGAoVOcBXYb8aDmkZUEHiyb1HeUjY9uqCsVvy', 'manager', '2026-11-14 11:55:07'),
('INS-HEBATULLAH', 'Hebatullah', 'hebatullah@zu.edu.jo', '$2y$12$WUYI.QoHgXc1peT6Rl/GM.9WmuzlF5exZkzf/u4Xopr8hOHl4VhGi', 'instructor', '2026-11-14 11:55:06'),
('STU-202300666', 'Raheem', '202300666@zu.edu.jo', '$2y$12$G1rpxA5wPC8kF8R6EAsQh.XSJYNwsS1zMnMdxxXvUpJ7Q7LPon/ty', 'student', '2026-11-14 11:55:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `idx_courses_department` (`department_code`);

--
-- Indexes for table `course_members`
--
ALTER TABLE `course_members`
  ADD PRIMARY KEY (`user_id`,`offering_id`),
  ADD KEY `idx_members_offering` (`offering_id`);

--
-- Indexes for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD PRIMARY KEY (`offering_id`),
  ADD KEY `idx_offerings_course` (`course_id`),
  ADD KEY `idx_offerings_instructor` (`instructor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_code`),
  ADD KEY `idx_departments_college` (`college_code`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `idx_lessons_offering_order` (`offering_id`,`lesson_order`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_offering` (`offering_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`user_id`,`lesson_id`),
  ADD KEY `idx_progress_lesson` (`lesson_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`user_id`,`offering_id`),
  ADD KEY `idx_reviews_offering` (`offering_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_department` FOREIGN KEY (`department_code`) REFERENCES `departments` (`department_code`);

--
-- Constraints for table `course_members`
--
ALTER TABLE `course_members`
  ADD CONSTRAINT `fk_members_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD CONSTRAINT `fk_offerings_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_offerings_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_college` FOREIGN KEY (`college_code`) REFERENCES `colleges` (`college_code`);

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_lessons_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_offering` FOREIGN KEY (`offering_id`) REFERENCES `course_offerings` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
