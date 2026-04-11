-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2026 at 11:02 PM
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
-- Database: `grad`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `role_level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `specialist_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `type` enum('online','onsite') DEFAULT NULL,
  `report` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `badge`
--

CREATE TABLE `badge` (
  `badge_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `behavior`
--

CREATE TABLE `behavior` (
  `behavior_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `behavior_type` varchar(100) DEFAULT NULL,
  `behavior_details` text DEFAULT NULL,
  `indicator` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `behavior_category`
--

CREATE TABLE `behavior_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `category_type` varchar(100) DEFAULT NULL,
  `category_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child`
--

CREATE TABLE `child` (
  `ssn` varchar(20) NOT NULL,
  `child_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birth_day` int(11) DEFAULT NULL,
  `birth_month` int(11) DEFAULT NULL,
  `birth_year` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`child_id`),
  UNIQUE KEY `ssn` (`ssn`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `child`
--
DELIMITER $$
CREATE TRIGGER `trg_child_insert` AFTER INSERT ON `child` FOR EACH ROW BEGIN
    UPDATE parent
    SET number_of_children = number_of_children + 1
    WHERE parent_id = NEW.parent_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `child_badge`
--

CREATE TABLE `child_badge` (
  `child_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_exhibited_behavior`
--

CREATE TABLE `child_exhibited_behavior` (
  `child_id` int(11) NOT NULL,
  `behavior_id` int(11) NOT NULL,
  `frequency` int(11) DEFAULT NULL,
  `severity` varchar(100) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_generated_system_report`
--

CREATE TABLE `child_generated_system_report` (
  `child_id` int(11) NOT NULL,
  `report` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_last_login`
--

CREATE TABLE `child_last_login` (
  `child_id` int(11) NOT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic`
--

CREATE TABLE `clinic` (
  `clinic_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rating` decimal(3,2) DEFAULT 0.00,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_phone`
--

CREATE TABLE `clinic_phone` (
  `clinic_id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `specialist_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `growth_record`
--

CREATE TABLE `growth_record` (
  `record_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `head_circumference` decimal(5,2) DEFAULT NULL,
  `arm_circumference` decimal(5,2) DEFAULT NULL,
  `subscapular_skinfold` decimal(5,2) DEFAULT NULL,
  `triceps_skinfold` decimal(5,2) DEFAULT NULL,
  `motor_milestones_score` int(11) DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

CREATE TABLE `parent` (
  `parent_id` int(11) NOT NULL,
  `number_of_children` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_subscription`
--

CREATE TABLE `parent_subscription` (
  `parent_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `child_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `amount_pre_discount` decimal(10,2) DEFAULT NULL,
  `discount_rate` decimal(5,2) DEFAULT NULL,
  `amount_post_discount` decimal(10,2) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `payment`
--
DELIMITER $$
CREATE TRIGGER `trg_payment_before_insert` BEFORE INSERT ON `payment` FOR EACH ROW BEGIN
    SET NEW.amount_post_discount =
        NEW.amount_pre_discount -
        (NEW.amount_pre_discount * NEW.discount_rate);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `points_refrence`
--

CREATE TABLE `points_refrence` (
  `refrence_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_name` varchar(100) DEFAULT NULL,
  `points_value` int(11) DEFAULT NULL,
  `adjust_sign` enum('+','-') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `points_transaction`
--

CREATE TABLE `points_transaction` (
  `transaction_id` int(11) NOT NULL,
  `refrence_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `points_change` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `points_transaction`
--
DELIMITER $$
CREATE TRIGGER `trg_points_transaction_before_insert` BEFORE INSERT ON `points_transaction` FOR EACH ROW BEGIN
    DECLARE p INT;
    DECLARE s ENUM('+','-');

    SELECT points_value, adjust_sign
    INTO p, s
    FROM points_refrence
    WHERE refrence_id = NEW.refrence_id;

    IF s = '+' THEN
        SET NEW.points_change = p;
        SET NEW.transaction_type = 'deposit';
    ELSE
        SET NEW.points_change = -p;
        SET NEW.transaction_type = 'withdrawal';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_points_wallet_after_insert` AFTER INSERT ON `points_transaction` FOR EACH ROW BEGIN
    UPDATE points_wallet
    SET total_points = total_points + NEW.points_change
    WHERE wallet_id = NEW.wallet_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `points_wallet`
--

CREATE TABLE `points_wallet` (
  `wallet_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialist`
--

CREATE TABLE `specialist` (
  `specialist_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `certificate_of_experience` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `speech_analysis`
--

CREATE TABLE `speech_analysis` (
  `sample_id` int(11) NOT NULL,
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transcript` text DEFAULT NULL,
  `vocabulary_score` decimal(5,2) DEFAULT NULL,
  `clarify_score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

CREATE TABLE `subscription` (
  `subscription_id` int(11) NOT NULL,
  `plan_name` varchar(100) DEFAULT NULL,
  `plan_period` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voice_sample`
--

CREATE TABLE `voice_sample` (
  `sample_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `specialist_id` (`specialist_id`);

--
-- Indexes for table `badge`
--
ALTER TABLE `badge`
  ADD PRIMARY KEY (`badge_id`);

--
-- Indexes for table `behavior`
--
ALTER TABLE `behavior`
  ADD PRIMARY KEY (`behavior_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `behavior_category`
--
ALTER TABLE `behavior_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `child` (already defined inline in CREATE TABLE)
--
-- ALTER TABLE `child`
--   ADD PRIMARY KEY (`child_id`),
--   ADD UNIQUE KEY `ssn` (`ssn`),
--   ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `child_badge`
--
ALTER TABLE `child_badge`
  ADD PRIMARY KEY (`child_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `child_exhibited_behavior`
--
ALTER TABLE `child_exhibited_behavior`
  ADD PRIMARY KEY (`child_id`,`behavior_id`),
  ADD KEY `behavior_id` (`behavior_id`);

--
-- Indexes for table `child_generated_system_report`
--
ALTER TABLE `child_generated_system_report`
  ADD PRIMARY KEY (`child_id`,`report`);

--
-- Indexes for table `child_last_login`
--
ALTER TABLE `child_last_login`
  ADD PRIMARY KEY (`child_id`,`login_at`);

--
-- Indexes for table `clinic`
--
ALTER TABLE `clinic`
  ADD PRIMARY KEY (`clinic_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `clinic_phone`
--
ALTER TABLE `clinic_phone`
  ADD PRIMARY KEY (`clinic_id`,`phone`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `specialist_id` (`specialist_id`);

--
-- Indexes for table `growth_record`
--
ALTER TABLE `growth_record`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `parent`
--
ALTER TABLE `parent`
  ADD PRIMARY KEY (`parent_id`);

--
-- Indexes for table `parent_subscription`
--
ALTER TABLE `parent_subscription`
  ADD PRIMARY KEY (`parent_id`,`subscription_id`,`child_name`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `points_refrence`
--
ALTER TABLE `points_refrence`
  ADD PRIMARY KEY (`refrence_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `points_transaction`
--
ALTER TABLE `points_transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `refrence_id` (`refrence_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- Indexes for table `points_wallet`
--
ALTER TABLE `points_wallet`
  ADD PRIMARY KEY (`wallet_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `specialist`
--
ALTER TABLE `specialist`
  ADD PRIMARY KEY (`specialist_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `specialist_id` (`specialist_id`);

--
-- Indexes for table `speech_analysis`
--
ALTER TABLE `speech_analysis`
  ADD PRIMARY KEY (`sample_id`,`analyzed_at`);

--
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`subscription_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `voice_sample`
--
ALTER TABLE `voice_sample`
  ADD PRIMARY KEY (`sample_id`),
  ADD KEY `child_id` (`child_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `badge`
--
ALTER TABLE `badge`
  MODIFY `badge_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `behavior`
--
ALTER TABLE `behavior`
  MODIFY `behavior_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `behavior_category`
--
ALTER TABLE `behavior_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic`
--
ALTER TABLE `clinic`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `growth_record`
--
ALTER TABLE `growth_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `points_refrence`
--
ALTER TABLE `points_refrence`
  MODIFY `refrence_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `points_transaction`
--
ALTER TABLE `points_transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `points_wallet`
--
ALTER TABLE `points_wallet`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specialist`
--
ALTER TABLE `specialist`
  MODIFY `specialist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription`
--
ALTER TABLE `subscription`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voice_sample`
--
ALTER TABLE `voice_sample`
  MODIFY `sample_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`),
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`);

--
-- Constraints for table `behavior`
--
ALTER TABLE `behavior`
  ADD CONSTRAINT `behavior_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `behavior_category` (`category_id`);

--
-- Constraints for table `child`
--
ALTER TABLE `child`
  ADD CONSTRAINT `child_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`);

--
-- Constraints for table `child_badge`
--
ALTER TABLE `child_badge`
  ADD CONSTRAINT `child_badge_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`),
  ADD CONSTRAINT `child_badge_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badge` (`badge_id`);

--
-- Constraints for table `child_exhibited_behavior`
--
ALTER TABLE `child_exhibited_behavior`
  ADD CONSTRAINT `child_exhibited_behavior_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`),
  ADD CONSTRAINT `child_exhibited_behavior_ibfk_2` FOREIGN KEY (`behavior_id`) REFERENCES `behavior` (`behavior_id`);

--
-- Constraints for table `child_generated_system_report`
--
ALTER TABLE `child_generated_system_report`
  ADD CONSTRAINT `child_generated_system_report_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`);

--
-- Constraints for table `child_last_login`
--
ALTER TABLE `child_last_login`
  ADD CONSTRAINT `child_last_login_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`);

--
-- Constraints for table `clinic`
--
ALTER TABLE `clinic`
  ADD CONSTRAINT `clinic_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `clinic_phone`
--
ALTER TABLE `clinic_phone`
  ADD CONSTRAINT `clinic_phone_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinic` (`clinic_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`);

--
-- Constraints for table `growth_record`
--
ALTER TABLE `growth_record`
  ADD CONSTRAINT `growth_record_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`);

--
-- Constraints for table `parent`
--
ALTER TABLE `parent`
  ADD CONSTRAINT `parent_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `parent_subscription`
--
ALTER TABLE `parent_subscription`
  ADD CONSTRAINT `parent_subscription_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  ADD CONSTRAINT `parent_subscription_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`);

--
-- Constraints for table `points_refrence`
--
ALTER TABLE `points_refrence`
  ADD CONSTRAINT `points_refrence_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `points_transaction`
--
ALTER TABLE `points_transaction`
  ADD CONSTRAINT `points_transaction_ibfk_1` FOREIGN KEY (`refrence_id`) REFERENCES `points_refrence` (`refrence_id`),
  ADD CONSTRAINT `points_transaction_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `points_wallet` (`wallet_id`);

--
-- Constraints for table `points_wallet`
--
ALTER TABLE `points_wallet`
  ADD CONSTRAINT `points_wallet_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`);

--
-- Constraints for table `specialist`
--
ALTER TABLE `specialist`
  ADD CONSTRAINT `fk_specialist_user` FOREIGN KEY (`specialist_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `specialist_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinic` (`clinic_id`);

--
-- Constraints for table `speech_analysis`
--
ALTER TABLE `speech_analysis`
  ADD CONSTRAINT `speech_analysis_ibfk_1` FOREIGN KEY (`sample_id`) REFERENCES `voice_sample` (`sample_id`);

--
-- Constraints for table `voice_sample`
--
ALTER TABLE `voice_sample`
  ADD CONSTRAINT `voice_sample_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`);

-- =====================================================
-- Additional tables for APIs (Notifications, Email, Auth)
-- =====================================================

--
-- Table structure for table `notifications`
--
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('appointment_reminder','payment_success','growth_alert','milestone','system') DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `email_logs`
--
CREATE TABLE IF NOT EXISTS `email_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template_type` varchar(50) DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `recipient_email` (`recipient_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `password_reset_tokens`
--
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Additional tables for new APIs
-- (Rate Limiting, Child Profile, Audit Log, Session
--  Management, Milestone Tracking, Gamification)
-- =====================================================

--
-- Table structure for table `audit_logs`
--
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'login, logout, register, password_change, etc.',
  `resource` varchar(100) DEFAULT NULL COMMENT 'users, children, appointments, payments, etc.',
  `resource_id` varchar(50) DEFAULT NULL COMMENT 'ID of the affected resource',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `details` text DEFAULT NULL COMMENT 'JSON with extra context',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_resource` (`resource`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_sessions`
--
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` varchar(36) NOT NULL COMMENT 'UUID',
  `user_id` int(11) NOT NULL,
  `token_jti` varchar(36) DEFAULT NULL COMMENT 'JWT ID for blacklisting',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_session_user` (`user_id`),
  KEY `idx_session_active` (`is_active`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `token_blacklist`
--
CREATE TABLE IF NOT EXISTS `token_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token_jti` varchar(36) NOT NULL COMMENT 'JWT jti claim',
  `user_id` int(11) NOT NULL,
  `expires_at` datetime NOT NULL COMMENT 'Token original expiry',
  `blacklisted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_jti` (`token_jti`),
  KEY `idx_blacklist_user` (`user_id`),
  KEY `idx_blacklist_expires` (`expires_at`),
  CONSTRAINT `token_blacklist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `rate_limit_log`
--
CREATE TABLE IF NOT EXISTS `rate_limit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(200) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rate_ip_endpoint` (`ip_address`, `endpoint`),
  KEY `idx_rate_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `blocked_ips`
--
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL COMMENT 'admin user_id',
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL COMMENT 'NULL = permanent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `milestones`
--
CREATE TABLE IF NOT EXISTS `milestones` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('motor_skills','language','cognitive','social_emotional','self_care') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `min_age_months` int(11) NOT NULL DEFAULT 0,
  `max_age_months` int(11) NOT NULL DEFAULT 72,
  PRIMARY KEY (`milestone_id`),
  KEY `idx_milestone_category` (`category`),
  KEY `idx_milestone_age` (`min_age_months`, `max_age_months`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `child_milestones`
--
CREATE TABLE IF NOT EXISTS `child_milestones` (
  `child_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `achieved_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`child_id`, `milestone_id`),
  KEY `milestone_id` (`milestone_id`),
  CONSTRAINT `child_milestones_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE,
  CONSTRAINT `child_milestones_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`milestone_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `streaks`
--
CREATE TABLE IF NOT EXISTS `streaks` (
  `streak_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `streak_type` varchar(50) NOT NULL COMMENT 'growth_tracking, milestone_logging, daily_login',
  `current_count` int(11) DEFAULT 0,
  `longest_count` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`streak_id`),
  UNIQUE KEY `child_streak_type` (`child_id`, `streak_type`),
  CONSTRAINT `streaks_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seed data: Default Milestones (48 milestones across 5 categories)
--
INSERT IGNORE INTO `milestones` (`category`, `title`, `description`, `min_age_months`, `max_age_months`) VALUES
-- Motor Skills
('motor_skills', 'Holds head up', 'Can hold head steady without support', 0, 4),
('motor_skills', 'Rolls over', 'Rolls from tummy to back and back to tummy', 3, 6),
('motor_skills', 'Sits without support', 'Sits steadily without needing help', 4, 9),
('motor_skills', 'Crawls', 'Moves on hands and knees', 6, 12),
('motor_skills', 'Pulls to stand', 'Pulls up to standing position using furniture', 8, 12),
('motor_skills', 'Walks independently', 'Takes steps without holding onto anything', 9, 18),
('motor_skills', 'Runs', 'Can run with good coordination', 18, 30),
('motor_skills', 'Kicks a ball', 'Can kick a ball forward', 18, 30),
('motor_skills', 'Climbs stairs with alternating feet', 'Goes up stairs one foot per step', 24, 42),
('motor_skills', 'Hops on one foot', 'Can hop on one foot several times', 36, 60),
('motor_skills', 'Catches a bounced ball', 'Can catch a ball that bounces', 36, 60),
('motor_skills', 'Draws a person with 6 body parts', 'Draws recognizable human figure', 48, 72),
-- Language
('language', 'Coos and babbles', 'Makes vowel sounds like "oo" and "ah"', 0, 6),
('language', 'Responds to name', 'Turns head when name is called', 4, 9),
('language', 'Says first word', 'Says a recognizable word like "mama" or "dada"', 9, 15),
('language', 'Says 10+ words', 'Uses at least 10 different words', 12, 24),
('language', 'Combines two words', 'Puts two words together like "more milk"', 18, 30),
('language', 'Uses short sentences', 'Speaks in 3-4 word sentences', 24, 36),
('language', 'Tells a simple story', 'Can narrate a short event or story', 36, 60),
('language', 'Asks "why" questions', 'Frequently asks why things happen', 30, 48),
('language', 'Uses past tense correctly', 'Says things like "I walked"', 36, 60),
('language', 'Knows most letters of alphabet', 'Can identify uppercase letters', 48, 72),
-- Cognitive
('cognitive', 'Follows moving objects with eyes', 'Tracks objects visually', 0, 4),
('cognitive', 'Finds hidden objects', 'Understands object permanence', 6, 12),
('cognitive', 'Stacks 2+ blocks', 'Can stack blocks on top of each other', 12, 24),
('cognitive', 'Sorts shapes and colors', 'Groups objects by shape or color', 18, 36),
('cognitive', 'Counts to 10', 'Can count objects up to 10', 30, 48),
('cognitive', 'Understands "same" and "different"', 'Can identify similarities and differences', 36, 48),
('cognitive', 'Knows basic colors', 'Names at least 4 colors correctly', 30, 48),
('cognitive', 'Understands time concepts', 'Grasps today/tomorrow/yesterday', 36, 60),
('cognitive', 'Writes own name', 'Can write first name', 48, 72),
-- Social-Emotional
('social_emotional', 'Social smile', 'Smiles in response to others', 0, 4),
('social_emotional', 'Shows stranger anxiety', 'Becomes upset around unfamiliar people', 6, 12),
('social_emotional', 'Plays alongside other children', 'Parallel play with peers', 18, 36),
('social_emotional', 'Shows empathy', 'Comforts a crying child or shows concern', 18, 36),
('social_emotional', 'Takes turns in play', 'Can share and take turns with others', 30, 48),
('social_emotional', 'Has a best friend', 'Forms a preferred friendship', 36, 60),
('social_emotional', 'Follows rules in simple games', 'Understands and follows game rules', 36, 60),
('social_emotional', 'Expresses complex emotions', 'Can describe feelings like frustration or excitement', 48, 72),
-- Self-Care
('self_care', 'Drinks from a cup', 'Holds and drinks from a cup with help', 6, 15),
('self_care', 'Uses a spoon', 'Feeds self with a spoon', 12, 24),
('self_care', 'Helps with dressing', 'Pulls off simple clothing items', 12, 24),
('self_care', 'Toilet trained (daytime)', 'Uses toilet independently during the day', 24, 42),
('self_care', 'Washes hands independently', 'Can wash and dry hands alone', 24, 42),
('self_care', 'Brushes teeth with help', 'Participates in tooth brushing', 18, 36),
('self_care', 'Dresses independently', 'Puts on clothes without help', 36, 60),
('self_care', 'Ties shoelaces', 'Can tie own shoes', 48, 72);

-- =====================================================
-- Doctor Dashboard: Reports & Messages Tables
-- =====================================================

--
-- Table structure for table `doctor_report`
--
CREATE TABLE IF NOT EXISTS `doctor_report` (
  `doctor_report_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `child_report` varchar(255) DEFAULT NULL COMMENT 'Reference to child_generated_system_report.report',
  `doctor_notes` text NOT NULL,
  `recommendations` text DEFAULT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doctor_report_id`),
  KEY `idx_dr_specialist` (`specialist_id`),
  KEY `idx_dr_child` (`child_id`),
  CONSTRAINT `doctor_report_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_report_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `message`
--
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `child_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  KEY `idx_msg_appointment` (`appointment_id`),
  KEY `idx_msg_child` (`child_id`),
  CONSTRAINT `message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `message_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `message_ibfk_4` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Admin Dashboard Tables
-- =====================================================

--
-- Table structure for table `activity_log`
--
CREATE TABLE IF NOT EXISTS `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `user_name` varchar(200) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `related_user_id` (`related_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `platform_settings`
--
CREATE TABLE IF NOT EXISTS `platform_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `subscription_feature`
--
CREATE TABLE IF NOT EXISTS `subscription_feature` (
  `feature_id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `feature_text` varchar(255) NOT NULL,
  PRIMARY KEY (`feature_id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `sub_feature_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Default Platform Settings
INSERT IGNORE INTO `platform_settings` (`setting_key`, `setting_value`) VALUES
('allow_clinic_registration', '1'),
('auto_approve_clinics', '0'),
('enable_free_trial', '1'),
('weekly_digest', '1'),
('maintenance_mode', '0');

-- Sample Admin User (password is: password)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Super', 'Admin', 'admin@brightsteps.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

SET @admin_user_id = LAST_INSERT_ID();

INSERT INTO `admin` (`admin_id`, `role_level`) VALUES (@admin_user_id, 1);

-- Sample Parent Users
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Sarah', 'Johnson', 'sarah.j@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active'),
('Michael', 'Thompson', 'michael.t@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active'),
('Jennifer', 'Williams', 'jennifer.w@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'inactive'),
('Ahmed', 'Hassan', 'ahmed.parent@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active');

SET @p1 = LAST_INSERT_ID();

INSERT IGNORE INTO `parent` (`parent_id`, `number_of_children`) VALUES
(@p1, 2), (@p1 + 1, 1), (@p1 + 2, 1), (@p1 + 3, 2);

-- Sample Specialist Users
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
('Sarah', 'Mitchell', 'sarah.m@citykids.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'specialist', 'active'),
('Ahmed', 'Hassan', 'ahmed.h@sunrisepeds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'specialist', 'active'),
('Layla', 'Noor', 'layla.n@citykids.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'specialist', 'active');

-- Sample Clinics
INSERT INTO `clinic` (`admin_id`, `clinic_name`, `email`, `password`, `location`, `status`, `rating`) VALUES
(@admin_user_id, 'City Kids Care', 'info@citykidscare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Downtown Blvd', 'verified', 4.80),
(@admin_user_id, 'Sunrise Pediatrics', 'hello@sunrisepeds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Avenue', 'verified', 4.60),
(@admin_user_id, 'Happy Smiles Clinic', 'contact@happysmiles.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Maple Street', 'verified', 4.90),
(@admin_user_id, 'Little Stars Wellness', 'info@littlestars.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Elm Drive', 'pending', 4.40);

-- Sample Subscription Plans
INSERT INTO `subscription` (`plan_name`, `plan_period`, `price`) VALUES
('Free Trial', 'monthly', 0.00),
('Standard', 'monthly', 9.99),
('Premium', 'monthly', 24.99);

SET @sub_free = LAST_INSERT_ID();
SET @sub_std = @sub_free + 1;
SET @sub_prem = @sub_free + 2;

INSERT INTO `subscription_feature` (`subscription_id`, `feature_text`) VALUES
(@sub_free, 'Basic growth tracking'),
(@sub_free, '1 child profile'),
(@sub_free, 'Monthly reports'),
(@sub_std, 'AI-powered insights'),
(@sub_std, 'Up to 3 child profiles'),
(@sub_std, 'Weekly reports'),
(@sub_std, 'Speech analysis'),
(@sub_prem, 'Everything in Standard'),
(@sub_prem, 'Unlimited child profiles'),
(@sub_prem, 'Doctor consultations'),
(@sub_prem, 'Priority support'),
(@sub_prem, '1-on-1 specialist access');

-- Sample Points Rules
INSERT INTO `points_refrence` (`admin_id`, `action_name`, `points_value`, `adjust_sign`) VALUES
(@admin_user_id, 'Daily Login', 10, '+'),
(@admin_user_id, 'Growth Measurement', 25, '+'),
(@admin_user_id, 'Voice Sample Upload', 50, '+'),
(@admin_user_id, 'Complete Weekly Goal', 100, '+'),
(@admin_user_id, 'Redeem Badge', 200, '-'),
(@admin_user_id, 'Missed Check-in', 5, '-');

-- Sample Behavior Categories
INSERT INTO `behavior_category` (`category_name`, `category_type`, `category_description`) VALUES
('Motor Development', 'Physical', 'Tracks gross and fine motor skill progression'),
('Speech and Language', 'Communication', 'Tracks verbal and non-verbal communication skills'),
('Social Interaction', 'Social-Emotional', 'Tracks social behavior and emotional development'),
('Cognitive Skills', 'Cognitive', 'Tracks problem-solving and learning abilities'),
('Self-Care', 'Adaptive', 'Tracks self-care and daily living skills');

-- Sample Activity Logs
INSERT INTO `activity_log` (`activity_type`, `description`, `related_user_id`, `created_at`) VALUES
('clinic_registered', 'New Clinic registered: Sunrise Pediatrics', NULL, NOW() - INTERVAL 2 HOUR),
('user_signup', 'New User signed up: Ahmed Hassan (Parent)', NULL, NOW() - INTERVAL 3 HOUR),
('subscription_upgrade', 'Subscription Upgrade: Sarah Johnson to Premium', NULL, NOW() - INTERVAL 5 HOUR),
('payment_received', 'Payment Received: $300.00 from Michael Thompson', NULL, NOW() - INTERVAL 6 HOUR),
('specialist_added', 'New Specialist added: Dr. Layla Noor at City Kids Care', NULL, NOW() - INTERVAL 8 HOUR),
('alert', 'Alert: 3 children flagged for developmental review', NULL, NOW() - INTERVAL 1 DAY),
('user_signup', 'New User signed up: Jennifer Williams (Parent)', NULL, NOW() - INTERVAL 2 DAY),
('clinic_verified', 'Clinic verified: Happy Smiles Clinic', NULL, NOW() - INTERVAL 3 DAY),
('payment_received', 'Payment Received: $150.00 from Jennifer Williams', NULL, NOW() - INTERVAL 4 DAY),
('system_update', 'Platform updated to version 2.1.0', NULL, NOW() - INTERVAL 5 DAY);

-- Sample Badges
INSERT INTO `badge` (`name`, `description`, `icon`) VALUES
('First Steps', 'Complete your first growth measurement', 'first_steps'),
('Voice Hero', 'Upload 5 voice samples', 'voice_hero'),
('Weekly Champion', 'Complete 4 weekly goals in a row', 'weekly_champion'),
('Growth Tracker', 'Log 10 growth measurements', 'growth_tracker'),
('Super Parent', 'Login for 30 consecutive days', 'super_parent');

-- =====================================================
-- Admin Dashboard Expansion Tables
-- =====================================================

--
-- Table: admin_notifications (admin-created bulk/targeted notifications)
--
CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `type` enum('in_app','email','both') DEFAULT 'in_app',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `target_type` enum('all','specific','segment') DEFAULT 'all',
  `target_filter` text DEFAULT NULL COMMENT 'JSON: user IDs or segment criteria',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` enum('draft','scheduled','sent','cancelled','failed') DEFAULT 'draft',
  `recipient_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_notif_status` (`status`),
  KEY `idx_admin_notif_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: admin_notification_recipients (per-user delivery tracking)
--
CREATE TABLE IF NOT EXISTS `admin_notification_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `delivered` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_anr_notif` (`notification_id`),
  KEY `idx_anr_user` (`user_id`),
  CONSTRAINT `anr_notif_fk` FOREIGN KEY (`notification_id`) REFERENCES `admin_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anr_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: flagged_content (content moderation)
--
CREATE TABLE IF NOT EXISTS `flagged_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(50) NOT NULL COMMENT 'feedback, message, report',
  `content_id` int(11) NOT NULL,
  `content_text` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'user who posted the content',
  `reason` varchar(255) DEFAULT NULL,
  `flagged_by` varchar(50) DEFAULT 'auto' COMMENT 'auto or user_id',
  `status` enum('pending','approved','removed','warned') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fc_status` (`status`),
  KEY `idx_fc_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: moderation_log (admin moderation actions)
--
CREATE TABLE IF NOT EXISTS `moderation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'approve, remove, warn, ban',
  `target_user_id` int(11) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ml_admin` (`admin_id`),
  KEY `idx_ml_target` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: system_logs (API errors, performance)
--
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` enum('info','warning','error','critical') DEFAULT 'info',
  `message` text NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `request_payload` text DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sl_level` (`level`),
  KEY `idx_sl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: admin_roles (role definitions with permissions)
--
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON array of permission strings',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default admin roles
INSERT IGNORE INTO `admin_roles` (`name`, `description`, `permissions`) VALUES
('Super Admin', 'Full access to all features', '["all"]'),
('Moderator', 'Can moderate content and manage users', '["users","moderation","tickets"]'),
('Support Agent', 'Can manage support tickets', '["tickets","users_view"]'),
('Analyst', 'View-only access to analytics', '["overview","reports","revenue","marketing"]');

--
-- Table: support_tickets
--
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_st_user` (`user_id`),
  KEY `idx_st_status` (`status`),
  KEY `idx_st_assigned` (`assigned_to`),
  CONSTRAINT `st_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: ticket_messages (conversation thread)
--
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') DEFAULT 'user',
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Internal admin notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tm_ticket` (`ticket_id`),
  CONSTRAINT `tm_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: announcement_banners
--
CREATE TABLE IF NOT EXISTS `announcement_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `style` enum('info','warning','success','error') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `target_audience` enum('all','parents','specialists','admins') DEFAULT 'all',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ab_active` (`is_active`),
  KEY `idx_ab_dates` (`starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed sample data
INSERT IGNORE INTO `support_tickets` (`user_id`, `subject`, `priority`, `status`) VALUES
(2, 'Cannot access growth charts', 'high', 'open'),
(3, 'Billing question about Premium plan', 'medium', 'in_progress'),
(4, 'Account recovery request', 'high', 'waiting');

INSERT IGNORE INTO `announcement_banners` (`message`, `style`, `target_audience`, `is_active`) VALUES
('Welcome to Bright Steps v2.1! Check out the new growth tracking features.', 'info', 'all', 1);

INSERT IGNORE INTO `system_logs` (`level`, `message`, `endpoint`, `method`, `response_time_ms`) VALUES
('info', 'System started successfully', '/admin/overview.php', 'GET', 45),
('warning', 'Slow query detected: 2.3s', '/api_who_compare.php', 'GET', 2300),
('error', 'Failed to send notification email', '/api_email_verify.php', 'POST', 1500);

-- =====================================================
-- Parent Dashboard Enhancement Tables
-- =====================================================

--
-- Table: user_settings (per-user preferences)
--
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int(11) NOT NULL,
  `theme` enum('light','dark') DEFAULT 'light',
  `language` enum('en','ar') DEFAULT 'en',
  `push_notifications` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `appointment_reminders` tinyint(1) DEFAULT 1,
  `daily_reminders` tinyint(1) DEFAULT 1,
  `milestone_alerts` tinyint(1) DEFAULT 1,
  `data_sharing` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: child_activities (tracks AI-recommended and completed activities)
--
CREATE TABLE IF NOT EXISTS `child_activities` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('article','real_life','website_game','speech','motor','cognitive','social') DEFAULT 'real_life',
  `duration_minutes` int(11) DEFAULT 15,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `source` enum('ai','system','specialist') DEFAULT 'ai',
  `external_url` varchar(500) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_ca_child` (`child_id`),
  KEY `idx_ca_completed` (`is_completed`),
  KEY `idx_ca_category` (`category`),
  KEY `idx_ca_created` (`created_at`),
  CONSTRAINT `child_activities_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Additional streak-based badge seed data
INSERT IGNORE INTO `badge` (`name`, `description`, `icon`) VALUES
('Rising Star', 'Maintain a 3-day login streak', 'rising_star'),
('Consistency King', 'Maintain a 7-day login streak', 'consistency_king'),
('Weekly Champion', 'Complete 5 activities in one week', 'weekly_champion_streak'),
('Monthly Master', 'Complete 20 activities in one month', 'monthly_master'),
('Explorer', 'Try activities from 3 different categories', 'explorer'),
('Knowledge Seeker', 'Read 5 recommended articles', 'knowledge_seeker');

-- ── Contact Messages ──────────────────────────────
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Newsletter Subscribers ───────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed` tinyint(1) DEFAULT 1,
  `preferences` text DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Newsletter History ───────────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `content` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nh_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Onboarding ───────────────────────────────
CREATE TABLE IF NOT EXISTS `parent_onboarding` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `child_name` VARCHAR(100),
  `child_dob` DATE,
  `child_gender` VARCHAR(10),
  `primary_concerns` TEXT,
  `preferred_activities` TEXT,
  `development_goals` TEXT,
  `completed_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Article Tracking ───────────────────────────────
CREATE TABLE IF NOT EXISTS `article_reads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_title` VARCHAR(255) NOT NULL,
  `read_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Parent Onboarding ──────────────────────────────
CREATE TABLE IF NOT EXISTS `parent_onboarding` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `child_name` VARCHAR(100) NOT NULL,
  `child_dob` DATE DEFAULT NULL,
  `child_gender` VARCHAR(20) DEFAULT NULL,
  `primary_concerns` JSON DEFAULT NULL,
  `preferred_activities` JSON DEFAULT NULL,
  `development_goals` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Article Reads ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `article_reads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_title` VARCHAR(255) NOT NULL,
  `read_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Notifications ───────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'system',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Motor Milestones ───────────────────────────────
CREATE TABLE IF NOT EXISTS `motor_milestones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `child_id` INT NOT NULL,
  `milestone_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'gross_motor',
  `is_achieved` TINYINT(1) DEFAULT 0,
  `achieved_at` TIMESTAMP NULL DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Consultations ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `consultations` (
  `consultation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `specialist_id` INT NOT NULL,
  `child_id` INT DEFAULT NULL,
  `consultation_type` ENUM('video','voice','chat') DEFAULT 'video',
  `status` ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_at` DATETIME NOT NULL,
  `duration_minutes` INT DEFAULT 30,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`specialist_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`child_id`) REFERENCES `child`(`child_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Consultation Messages ──────────────────────────
CREATE TABLE IF NOT EXISTS `consultation_messages` (
  `message_id` INT AUTO_INCREMENT PRIMARY KEY,
  `consultation_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `sent_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`consultation_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Community Messages ─────────────────────────────
CREATE TABLE IF NOT EXISTS `community_messages` (
  `message_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `likes` INT DEFAULT 0,
  `reply_count` INT DEFAULT 0,
  `parent_message_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Article Reads ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `article_reads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_title` VARCHAR(255) NOT NULL,
  `read_at` TIMESTAMP DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

> "CREATE TABLE IF NOT EXISTS `motor_milestones` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `child_id` int(11) NOT NULL,
    `milestone_name` varchar(255) NOT NULL,
    `category` varchar(100) NOT NULL,
    `is_achieved` tinyint(1) DEFAULT 0,
    `achieved_at` datetime DEFAULT NULL,
    `created_at` timestamp DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `child_id` (`child_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
  
> "CREATE TABLE IF NOT EXISTS `child_activities` (
    `activity_id` int(11) NOT NULL AUTO_INCREMENT,
    `child_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text,
    `category` varchar(100) NOT NULL,
    `duration_minutes` int(11) DEFAULT 15,
    `difficulty` varchar(50) DEFAULT 'medium',
    `source` varchar(50) DEFAULT 'ai',
    `is_completed` tinyint(1) DEFAULT 0,
    `completed_at` datetime DEFAULT NULL,
    `points_earned` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT current_timestamp(),
    PRIMARY KEY (`activity_id`),
    KEY `child_id` (`child_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
  
> "CREATE TABLE IF NOT EXISTS `child_milestones` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `child_id` int(11) NOT NULL,
    `milestone_id` int(11) NOT NULL,
    `achieved_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `child_id` (`child_id`),
    KEY `milestone_id` (`milestone_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
  
  // Wait, milestones table might also be needed? Let's assume milestones exists or create it too
> "CREATE TABLE IF NOT EXISTS `milestones` (
    `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `category` varchar(100) NOT NULL,
    PRIMARY KEY (`milestone_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
  
> "CREATE TABLE IF NOT EXISTS `points_wallet` (
    `wallet_id` int(11) NOT NULL AUTO_INCREMENT,
    `child_id` int(11) NOT NULL,
    `total_points` int(11) DEFAULT 0,
    `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`wallet_id`),
    KEY `child_id` (`child_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
  ];
  
  foreach ($queries as $sql) {
      try {
          $connect->exec($sql);
          echo "Successfully executed query.\n";
      } catch (PDOException $e) {
          echo "Error: " . $e->getMessage() . "\n";
      }
  }
  echo "Migration finished.\n";
  ?>

<<<<<<< HEAD


DELIMITER 
CREATE TRIGGER trg_users_signup_log AFTER INSERT ON users FOR EACH ROW BEGIN
    INSERT INTO activity_log (activity_type, description, related_user_id, user_name, user_role, created_at)
    VALUES (
        'user_signup',
        CONCAT('New User signed up: ', IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.last_name, ''), ' (', NEW.role, ')'),
        NEW.user_id,
        CONCAT(IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.last_name, '')),
        NEW.role,
        NOW()
    );
END
DELIMITER ;

DELIMITER 
CREATE TRIGGER trg_clinic_insert_log AFTER INSERT ON clinic FOR EACH ROW BEGIN
    INSERT INTO activity_log (activity_type, description, created_at)
    VALUES (
        'clinic_registered',
        CONCAT('New Clinic registered: ', IFNULL(NEW.clinic_name, '')),
        NOW()
    );
END
DELIMITER ;

DELIMITER 
CREATE TRIGGER trg_clinic_update_log AFTER UPDATE ON clinic FOR EACH ROW BEGIN
    IF OLD.status != 'verified' AND NEW.status = 'verified' THEN
        INSERT INTO activity_log (activity_type, description, created_at)
        VALUES (
            'clinic_verified',
            CONCAT('Clinic verified: ', IFNULL(NEW.clinic_name, '')),
            NOW()
        );
    END IF;
END
DELIMITER ;

DELIMITER 
CREATE TRIGGER trg_payment_insert_log AFTER INSERT ON payment FOR EACH ROW BEGIN
    INSERT INTO activity_log (activity_type, description, created_at)
    VALUES (
        'payment_received',
        CONCAT('Payment Received: $', FORMAT(NEW.amount_post_discount, 2)),
        NOW()
    );
END
DELIMITER ;

DELIMITER 
CREATE TRIGGER trg_parent_sub_log AFTER INSERT ON parent_subscription FOR EACH ROW BEGIN
    INSERT INTO activity_log (activity_type, description, related_user_id, created_at)
    VALUES (
        'subscription_upgrade',
        CONCAT('Subscription Purchased (Plan ID: ', NEW.subscription_id, ')'),
        NEW.parent_id,
        NOW()
    );
END
DELIMITER ;

=======
>>>>>>> 39b714dea30f2934727c97f4ec8aff6d17893e00
