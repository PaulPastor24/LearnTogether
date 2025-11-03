-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2025 at 06:51 AM
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
-- Database: `learntogetherdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `learners`
--

CREATE TABLE `learners` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learners`
--

INSERT INTO `learners` (`id`, `user_id`, `grade_level`, `interests`, `profile_image`) VALUES
(3, 11, '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `tutor_name` varchar(150) NOT NULL,
  `status` enum('Pending','Accepted','Confirmed','Rejected','Cancelled') DEFAULT 'Pending',
  `session_date` date DEFAULT NULL,
  `requested_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_time_start` time DEFAULT NULL,
  `session_time_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `learner_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `duration` int(11) DEFAULT 60,
  `status` enum('Pending','Confirmed','Cancelled','Requested') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `learner_id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `agora_channel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `learner_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('In Progress','Interested','Completed','Beginner') DEFAULT 'In Progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `availability` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `rating` decimal(3,2) DEFAULT 0.00,
  `hours_taught` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `user_id`, `expertise`, `bio`, `profile_image`, `hourly_rate`, `availability`, `rating`, `hours_taught`) VALUES
(8, 10, '', '', '', 0.00, 'Available', 0.00, 0),
(9, 10, 'Mathematics & Physics', 'Expert in Math and Physics with 5+ years of experience.', NULL, 350.00, 'Available', 4.90, 150),
(10, 12, 'Advanced Mathematics', 'Focuses on problem-solving and advanced calculus.', NULL, 400.00, 'Available', 4.80, 120),
(11, 14, 'English & Communication', 'Specializes in English grammar and communication skills.', NULL, 300.00, 'Available', 4.70, 100),
(12, 16, 'Computer Science', 'Helps students understand programming and algorithms.', NULL, 500.00, 'Available', 4.90, 200);

-- --------------------------------------------------------

--
-- Table structure for table `tutor_subjects`
--

CREATE TABLE `tutor_subjects` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `topics` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutor_subjects`
--

INSERT INTO `tutor_subjects` (`id`, `tutor_id`, `subject_name`, `description`, `topics`) VALUES
(17, 10, 'Mathematics & Physics', 'Expert in Mathematics and Physics with 5+ years of tutoring experience.', 'Calculus, Algebra, Physics'),
(18, 12, 'Advanced Mathematics', 'Focuses on problem-solving and advanced calculus for college students.', 'Differential Equations, Linear Algebra, Trigonometry'),
(19, 14, 'English & Communication', 'Specializes in English grammar and communication skills.', 'Grammar, Speaking, Essay Writing'),
(20, 16, 'Computer Science', 'Teaches programming fundamentals and algorithm design.', 'Python, Java, Data Structures');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `phone`, `verified`, `otp_code`) VALUES
(10, 'paul', 'pastor', 'paulcedric71@gmail.com', '$2y$10$c/.HebVunRJaaFZLchdm7e3zWlKHfSU0cl9EqN7L1/Ptrzfrxzv9y', 'tutor', 'active', '639942497466', 1, NULL),
(11, 'paul', 'cedric', 'example@gamail.com', '$2y$10$WNM/5b55KiccCTC.rebqAuUqDu0ExmnJRhPgB4jGoL0hIJA7HyscG', 'learner', 'active', '639912577062', 1, NULL),
(12, 'John', 'Cruz', 'johncruz@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'tutor', 'active', '09171234567', 1, NULL),
(13, 'Maria', 'Santos', 'marias@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'learner', 'active', '09179876543', 1, NULL),
(14, 'Alex', 'Reyes', 'alexreyes@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'tutor', 'active', '09175551234', 1, '84321'),
(15, 'Ella', 'Dela Cruz', 'elladc@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'learner', 'active', '09171239876', 1, NULL),
(16, 'Kevin', 'Tan', 'kevintan@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'tutor', 'active', '09172345678', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `learners`
--
ALTER TABLE `learners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learner_id` (`learner_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `learner_id` (`learner_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learner_id` (`learner_id`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `learners`
--
ALTER TABLE `learners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `learners`
--
ALTER TABLE `learners`
  ADD CONSTRAINT `learners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutors`
--
ALTER TABLE `tutors`
  ADD CONSTRAINT `tutors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD CONSTRAINT `tutor_subjects_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
