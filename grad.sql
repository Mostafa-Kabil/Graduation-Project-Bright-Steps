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
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birth_day` int(11) DEFAULT NULL,
  `birth_month` int(11) DEFAULT NULL,
  `birth_year` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL
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
  `price` decimal(10,2) DEFAULT NULL
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
  `role` varchar(50) DEFAULT NULL
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
-- Indexes for table `child`
--
ALTER TABLE `child`
  ADD PRIMARY KEY (`ssn`,`child_id`),
  ADD UNIQUE KEY `child_id` (`child_id`),
  ADD KEY `parent_id` (`parent_id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
