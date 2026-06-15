-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: grad
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `role_level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,1,'2026-06-15 13:47:20');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notification_recipients`
--

DROP TABLE IF EXISTS `admin_notification_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notification_recipients` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notification_recipients`
--

LOCK TABLES `admin_notification_recipients` WRITE;
/*!40000 ALTER TABLE `admin_notification_recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notification_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notifications` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON array of permission strings',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Super Admin','Full access to all features','[\"all\"]','2026-04-04 22:23:27'),(2,'Moderator','Can moderate content and manage users','[\"users\",\"clinics\",\"points\",\"moderation\",\"tickets\"]','2026-04-04 22:23:27'),(3,'Support Agent','Can manage support tickets','[\"tickets\",\"users_view\"]','2026-04-04 22:23:27'),(4,'Analyst','View-only access to analytics','[\"overview\",\"reports\",\"revenue\",\"marketing\"]','2026-04-04 22:23:27');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_banners`
--

DROP TABLE IF EXISTS `announcement_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_banners` (
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
  KEY `idx_ab_dates` (`starts_at`,`ends_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_banners`
--

LOCK TABLES `announcement_banners` WRITE;
/*!40000 ALTER TABLE `announcement_banners` DISABLE KEYS */;
INSERT INTO `announcement_banners` VALUES (1,'Welcome to Bright Steps v2.1! Check out the new growth tracking features.','info','','all',NULL,NULL,0,NULL,'2026-04-04 22:23:27'),(2,'hiiiiiiii','success',NULL,'all','2421-02-21 02:03:00',NULL,1,1,'2026-04-25 14:13:48');
/*!40000 ALTER TABLE `announcement_banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment`
--

DROP TABLE IF EXISTS `appointment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) DEFAULT NULL,
  `payment_id` int(11) NOT NULL,
  `specialist_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `type` enum('online','onsite') DEFAULT NULL,
  `report` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `parent_id` (`parent_id`),
  KEY `payment_id` (`payment_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`),
  CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment`
--

LOCK TABLES `appointment` WRITE;
/*!40000 ALTER TABLE `appointment` DISABLE KEYS */;
INSERT INTO `appointment` VALUES (1,76,1,1,15,'Completed',NULL,NULL,'Initial assessment for speech.','2023-10-15 10:00:00',NULL),(2,76,1,2,15,'Completed',NULL,NULL,'Follow up speech therapy.','2023-11-20 10:00:00',NULL),(3,76,1,3,15,'Scheduled',NULL,NULL,'Regular speech session.','2026-06-17 16:47:21',NULL),(4,77,2,4,71,'Completed',NULL,NULL,'Routine checkup.','2024-08-04 07:53:47',NULL),(5,78,3,5,54,'Completed',NULL,NULL,'Routine checkup.','2023-06-19 03:44:30',NULL),(6,81,8,6,15,'Completed',NULL,NULL,'Routine checkup.','2024-06-29 15:04:15',NULL),(7,81,9,7,31,'Completed',NULL,NULL,'Routine checkup.','2024-04-16 06:22:03',NULL),(8,82,10,8,58,'Completed',NULL,NULL,'Routine checkup.','2024-11-29 06:07:21',NULL),(9,83,11,9,46,'Completed',NULL,NULL,'Routine checkup.','2024-06-21 21:54:17',NULL),(10,84,13,10,45,'Completed',NULL,NULL,'Routine checkup.','2024-06-19 07:19:27',NULL),(11,85,15,11,44,'Completed',NULL,NULL,'Routine checkup.','2023-01-14 05:50:19',NULL),(12,86,18,12,61,'Completed',NULL,NULL,'Routine checkup.','2023-05-01 16:49:24',NULL),(13,87,19,13,27,'Completed',NULL,NULL,'Routine checkup.','2023-11-23 09:20:29',NULL),(14,87,20,14,69,'Completed',NULL,NULL,'Routine checkup.','2024-11-16 09:04:21',NULL),(15,90,24,15,37,'Completed',NULL,NULL,'Routine checkup.','2023-12-27 12:54:11',NULL),(16,91,25,16,66,'Completed',NULL,NULL,'Routine checkup.','2023-11-10 18:45:53',NULL),(17,91,26,17,22,'Completed',NULL,NULL,'Routine checkup.','2023-02-19 06:51:03',NULL),(18,92,27,18,48,'Completed',NULL,NULL,'Routine checkup.','2023-07-10 00:22:48',NULL),(19,94,31,19,20,'Completed',NULL,NULL,'Routine checkup.','2024-06-20 09:56:27',NULL),(20,96,34,20,37,'Completed',NULL,NULL,'Routine checkup.','2023-02-10 11:57:05',NULL),(21,96,35,21,56,'Completed',NULL,NULL,'Routine checkup.','2023-05-09 03:02:03',NULL),(22,97,36,22,39,'Completed',NULL,NULL,'Routine checkup.','2024-01-21 22:44:11',NULL),(23,97,37,23,32,'Completed',NULL,NULL,'Routine checkup.','2024-09-17 22:01:39',NULL),(24,98,38,24,39,'Completed',NULL,NULL,'Routine checkup.','2024-03-18 11:00:44',NULL),(25,99,40,25,32,'Completed',NULL,NULL,'Routine checkup.','2023-11-15 00:31:27',NULL),(26,99,41,26,40,'Completed',NULL,NULL,'Routine checkup.','2024-09-07 09:10:33',NULL),(27,100,42,27,47,'Completed',NULL,NULL,'Routine checkup.','2024-01-12 01:07:37',NULL),(28,102,44,28,24,'Completed',NULL,NULL,'Routine checkup.','2024-02-03 11:44:09',NULL),(29,102,45,29,62,'Completed',NULL,NULL,'Routine checkup.','2024-02-24 14:18:16',NULL),(30,104,49,30,56,'Completed',NULL,NULL,'Routine checkup.','2024-03-29 22:07:38',NULL),(31,105,50,31,54,'Completed',NULL,NULL,'Routine checkup.','2024-09-12 11:07:16',NULL),(32,105,51,32,28,'Completed',NULL,NULL,'Routine checkup.','2023-04-12 17:12:50',NULL),(33,106,53,33,50,'Completed',NULL,NULL,'Routine checkup.','2023-10-07 09:20:32',NULL),(34,108,57,34,75,'Completed',NULL,NULL,'Routine checkup.','2024-12-07 13:18:02',NULL),(35,109,58,35,39,'Completed',NULL,NULL,'Routine checkup.','2024-11-05 13:15:04',NULL),(36,109,59,36,51,'Completed',NULL,NULL,'Routine checkup.','2024-01-18 00:23:22',NULL),(37,110,60,37,60,'Completed',NULL,NULL,'Routine checkup.','2023-10-24 23:28:28',NULL),(38,112,62,38,32,'Completed',NULL,NULL,'Routine checkup.','2024-03-14 09:55:42',NULL),(39,113,64,39,73,'Completed',NULL,NULL,'Routine checkup.','2023-09-17 04:22:54',NULL),(40,114,66,40,63,'Completed',NULL,NULL,'Routine checkup.','2024-09-20 14:47:11',NULL),(41,115,67,41,58,'Completed',NULL,NULL,'Routine checkup.','2023-10-15 01:11:20',NULL),(42,117,70,42,74,'Completed',NULL,NULL,'Routine checkup.','2023-06-04 01:47:36',NULL),(43,117,71,43,24,'Completed',NULL,NULL,'Routine checkup.','2023-06-14 12:47:43',NULL),(44,119,74,44,23,'Completed',NULL,NULL,'Routine checkup.','2023-10-02 12:13:44',NULL),(45,119,75,45,38,'Completed',NULL,NULL,'Routine checkup.','2023-01-26 09:14:46',NULL),(46,120,76,46,50,'Completed',NULL,NULL,'Routine checkup.','2023-05-20 00:00:28',NULL),(47,122,79,47,24,'Completed',NULL,NULL,'Routine checkup.','2024-08-02 04:37:22',NULL),(48,122,80,48,68,'Completed',NULL,NULL,'Routine checkup.','2023-03-24 17:40:26',NULL),(49,123,81,49,50,'Completed',NULL,NULL,'Routine checkup.','2023-09-25 17:43:13',NULL),(50,123,82,50,71,'Completed',NULL,NULL,'Routine checkup.','2023-05-04 11:00:45',NULL),(51,125,84,51,74,'Completed',NULL,NULL,'Routine checkup.','2024-04-15 08:51:06',NULL),(52,126,86,52,19,'Completed',NULL,NULL,'Routine checkup.','2023-03-23 06:38:35',NULL),(53,126,87,53,70,'Completed',NULL,NULL,'Routine checkup.','2024-09-18 02:33:01',NULL),(54,127,88,54,28,'Completed',NULL,NULL,'Routine checkup.','2023-06-25 17:59:21',NULL),(55,129,92,55,22,'Completed',NULL,NULL,'Routine checkup.','2024-09-24 05:13:44',NULL),(56,130,93,56,66,'Completed',NULL,NULL,'Routine checkup.','2024-09-06 01:41:01',NULL),(57,132,96,57,58,'Completed',NULL,NULL,'Routine checkup.','2024-12-19 15:47:37',NULL),(58,133,97,58,35,'Completed',NULL,NULL,'Routine checkup.','2023-03-21 22:35:29',NULL),(59,135,99,59,15,'Completed',NULL,NULL,'Routine checkup.','2023-03-09 14:31:20',NULL),(60,135,100,60,26,'Completed',NULL,NULL,'Routine checkup.','2024-05-02 09:23:06',NULL),(61,136,101,61,57,'Completed',NULL,NULL,'Routine checkup.','2024-08-29 14:31:28',NULL),(62,136,102,62,67,'Completed',NULL,NULL,'Routine checkup.','2024-02-17 08:31:59',NULL),(63,137,103,63,58,'Completed',NULL,NULL,'Routine checkup.','2024-08-15 07:14:04',NULL),(64,137,104,64,42,'Completed',NULL,NULL,'Routine checkup.','2024-07-28 11:12:39',NULL),(65,138,105,65,74,'Completed',NULL,NULL,'Routine checkup.','2024-04-28 17:18:33',NULL),(66,141,110,66,21,'Completed',NULL,NULL,'Routine checkup.','2024-11-02 18:16:56',NULL),(67,143,112,67,45,'Completed',NULL,NULL,'Routine checkup.','2024-12-11 14:31:58',NULL),(68,144,115,68,27,'Completed',NULL,NULL,'Routine checkup.','2024-05-04 20:44:40',NULL),(69,145,117,69,60,'Completed',NULL,NULL,'Routine checkup.','2023-08-22 21:24:40',NULL),(70,146,119,70,41,'Completed',NULL,NULL,'Routine checkup.','2024-06-18 19:43:31',NULL),(71,147,120,71,33,'Completed',NULL,NULL,'Routine checkup.','2023-02-07 07:59:47',NULL),(72,149,122,72,35,'Completed',NULL,NULL,'Routine checkup.','2024-08-27 16:07:00',NULL),(73,149,123,73,30,'Completed',NULL,NULL,'Routine checkup.','2024-12-01 23:16:34',NULL),(74,150,124,74,42,'Completed',NULL,NULL,'Routine checkup.','2024-08-09 23:27:40',NULL),(75,151,125,75,30,'Completed',NULL,NULL,'Routine checkup.','2023-09-08 21:28:13',NULL),(76,151,126,76,37,'Completed',NULL,NULL,'Routine checkup.','2024-08-17 15:18:48',NULL),(77,152,128,77,58,'Completed',NULL,NULL,'Routine checkup.','2023-06-28 00:08:01',NULL),(78,153,129,78,39,'Completed',NULL,NULL,'Routine checkup.','2023-04-20 22:31:01',NULL),(79,153,130,79,33,'Completed',NULL,NULL,'Routine checkup.','2024-06-01 03:34:36',NULL),(80,154,131,80,20,'Completed',NULL,NULL,'Routine checkup.','2023-04-01 15:01:38',NULL),(81,156,134,81,68,'Completed',NULL,NULL,'Routine checkup.','2023-05-05 15:55:05',NULL),(82,158,137,82,35,'Completed',NULL,NULL,'Routine checkup.','2023-09-28 10:19:13',NULL),(83,159,139,83,54,'Completed',NULL,NULL,'Routine checkup.','2023-03-14 04:04:52',NULL),(84,165,147,84,50,'Completed',NULL,NULL,'Routine checkup.','2024-06-22 05:40:43',NULL),(85,166,148,85,28,'Completed',NULL,NULL,'Routine checkup.','2023-10-02 19:53:09',NULL),(86,167,149,86,31,'Completed',NULL,NULL,'Routine checkup.','2024-09-12 23:08:11',NULL),(87,168,150,87,39,'Completed',NULL,NULL,'Routine checkup.','2024-02-19 21:05:27',NULL),(88,168,151,88,64,'Completed',NULL,NULL,'Routine checkup.','2024-06-18 16:41:35',NULL),(89,169,152,89,66,'Completed',NULL,NULL,'Routine checkup.','2024-11-26 15:04:04',NULL),(90,169,153,90,39,'Completed',NULL,NULL,'Routine checkup.','2023-01-13 04:39:05',NULL),(91,171,156,91,56,'Completed',NULL,NULL,'Routine checkup.','2023-02-23 09:47:03',NULL),(92,173,158,92,20,'Completed',NULL,NULL,'Routine checkup.','2023-10-04 02:03:53',NULL),(93,173,159,93,31,'Completed',NULL,NULL,'Routine checkup.','2023-05-29 09:03:52',NULL),(94,174,160,94,53,'Completed',NULL,NULL,'Routine checkup.','2024-05-10 23:56:54',NULL),(95,175,161,95,57,'Completed',NULL,NULL,'Routine checkup.','2023-05-09 16:42:08',NULL),(96,175,162,96,33,'Completed',NULL,NULL,'Routine checkup.','2024-12-08 06:16:10',NULL),(97,176,164,97,37,'Completed',NULL,NULL,'Routine checkup.','2024-02-05 10:25:32',NULL),(98,177,165,98,64,'Completed',NULL,NULL,'Routine checkup.','2024-06-15 08:50:54',NULL),(99,177,166,99,48,'Completed',NULL,NULL,'Routine checkup.','2023-06-12 20:43:50',NULL),(100,178,168,100,61,'Completed',NULL,NULL,'Routine checkup.','2023-02-28 06:22:49',NULL),(101,180,171,101,17,'Completed',NULL,NULL,'Routine checkup.','2024-09-20 19:28:15',NULL),(102,181,173,102,32,'Completed',NULL,NULL,'Routine checkup.','2023-06-29 12:00:51',NULL),(103,181,174,103,47,'Completed',NULL,NULL,'Routine checkup.','2024-01-01 09:42:53',NULL),(104,182,176,104,27,'Completed',NULL,NULL,'Routine checkup.','2024-06-13 00:54:49',NULL),(105,183,177,105,58,'Completed',NULL,NULL,'Routine checkup.','2024-09-12 13:10:23',NULL),(106,184,178,106,49,'Completed',NULL,NULL,'Routine checkup.','2024-03-22 23:53:04',NULL),(107,186,181,107,66,'Completed',NULL,NULL,'Routine checkup.','2023-10-05 12:12:14',NULL),(108,187,182,108,27,'Completed',NULL,NULL,'Routine checkup.','2023-08-29 02:13:23',NULL),(109,189,184,109,65,'Completed',NULL,NULL,'Routine checkup.','2023-09-21 21:42:01',NULL),(110,190,185,110,38,'Completed',NULL,NULL,'Routine checkup.','2024-12-20 00:49:28',NULL),(111,192,187,111,31,'Completed',NULL,NULL,'Routine checkup.','2024-11-16 22:38:41',NULL),(112,193,188,112,54,'Completed',NULL,NULL,'Routine checkup.','2023-09-28 23:30:19',NULL),(113,193,189,113,66,'Completed',NULL,NULL,'Routine checkup.','2023-10-03 02:19:34',NULL),(114,194,190,114,37,'Completed',NULL,NULL,'Routine checkup.','2023-07-30 08:31:08',NULL),(115,195,192,115,40,'Completed',NULL,NULL,'Routine checkup.','2023-05-20 23:36:57',NULL),(116,195,193,116,66,'Completed',NULL,NULL,'Routine checkup.','2024-07-31 01:31:35',NULL),(117,196,194,117,39,'Completed',NULL,NULL,'Routine checkup.','2023-08-30 13:20:54',NULL);
/*!40000 ALTER TABLE `appointment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_slots`
--

DROP TABLE IF EXISTS `appointment_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sun, 1=Mon, ..., 6=Sat',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'minutes per slot',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `uq_doctor_day` (`doctor_id`,`day_of_week`),
  KEY `idx_doctor` (`doctor_id`),
  KEY `idx_active` (`is_active`),
  KEY `as_clinic_fk` (`clinic_id`),
  CONSTRAINT `as_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_slots`
--

LOCK TABLES `appointment_slots` WRITE;
/*!40000 ALTER TABLE `appointment_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointment_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_tokens`
--

DROP TABLE IF EXISTS `appointment_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `redemption_id` int(11) DEFAULT NULL,
  `token_type` enum('discount_25','discount_50','free','extended','priority') NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('available','applied','used','expired') DEFAULT 'available',
  `applied_to_appointment` int(11) DEFAULT NULL,
  `expires_at` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `fk_token_redemption` (`redemption_id`),
  KEY `fk_token_appointment` (`applied_to_appointment`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_token_appointment` FOREIGN KEY (`applied_to_appointment`) REFERENCES `appointment` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_token_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_token_redemption` FOREIGN KEY (`redemption_id`) REFERENCES `parent_redemptions` (`redemption_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_tokens`
--

LOCK TABLES `appointment_tokens` WRITE;
/*!40000 ALTER TABLE `appointment_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointment_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article_reads`
--

DROP TABLE IF EXISTS `article_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `article_title` varchar(255) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `article_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_reads`
--

LOCK TABLES `article_reads` WRITE;
/*!40000 ALTER TABLE `article_reads` DISABLE KEYS */;
INSERT INTO `article_reads` VALUES (1,10,'Building Confidence in Children','2026-04-04 23:49:41'),(2,10,'Building Confidence in Children','2026-04-04 23:50:24'),(3,10,'Building Confidence in Children','2026-04-04 23:50:43'),(4,9,'School Readiness Checklist','2026-04-05 00:03:23'),(5,9,'School Readiness Checklist','2026-04-05 00:47:38'),(6,9,'School Readiness Checklist','2026-04-05 08:39:00'),(7,9,'School Readiness Checklist','2026-04-05 08:44:24'),(8,9,'School Readiness Checklist','2026-04-05 10:14:07'),(9,9,'School Readiness Checklist','2026-04-05 10:40:46'),(10,9,'School Readiness Checklist','2026-05-03 20:45:14'),(11,9,'School Readiness Checklist','2026-05-03 22:16:44'),(12,9,'School Readiness Checklist','2026-05-03 22:16:54'),(13,9,'School Readiness Checklist','2026-05-03 22:33:53'),(14,9,'School Readiness Checklist','2026-05-03 22:38:16'),(15,9,'Managing Tantrums Effectively','2026-05-03 22:38:23'),(16,9,'Screen Time Guidelines for Kids','2026-05-03 22:38:26'),(17,9,'Fostering Independence','2026-05-04 00:06:11'),(18,9,'Healthy Snacks for Energy','2026-05-04 00:06:24'),(19,9,'Healthy Nutrition for Infants','2026-05-04 00:22:31'),(20,9,'Toddler Speech: When to Worry','2026-05-22 18:07:08'),(21,9,'Toddler Hygiene Routines','2026-05-22 18:10:54'),(22,62,'Toddler Speech: When to Worry','2026-05-24 05:40:33'),(23,9,'Healthy Meals for Picky Eaters','2026-06-12 15:57:02');
/*!40000 ALTER TABLE `article_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
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
  KEY `idx_audit_resource` (`resource`,`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'update_user_status','users','9','::1',NULL,'Changed status to active for user: mostafa kabil','2026-06-11 22:46:06'),(2,1,'update_user','users','71','::1',NULL,'Updated user: mohamed mostafa','2026-06-15 11:27:56');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badge`
--

DROP TABLE IF EXISTS `badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `badge` (
  `badge_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`badge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badge`
--

LOCK TABLES `badge` WRITE;
/*!40000 ALTER TABLE `badge` DISABLE KEYS */;
INSERT INTO `badge` VALUES (1,'First Steps','Complete your first growth measurement','first_steps'),(2,'Voice Hero','Upload 5 voice samples','voice_hero'),(3,'Weekly Champion','Complete 4 weekly goals in a row','weekly_champion'),(4,'Growth Tracker','Log 10 growth measurements','growth_tracker'),(5,'Super Parent','Login for 30 consecutive days','super_parent'),(6,'Rising Star','Maintain a 3-day login streak','rising_star'),(7,'Consistency King','Maintain a 7-day login streak','consistency_king'),(9,'Monthly Master','Complete 20 activities in one month','monthly_master'),(19,'Article Reader','Read your first article','article_reader'),(20,'Bookworm','Read 10 articles','bookworm'),(21,'Speech Explorer','Complete 5 speech analyses','speech_explorer'),(22,'Motor Master','Complete 5 motor skill milestones','motor_master'),(23,'Health Champion','Log 5 growth measurements','health_champion'),(26,'Game Master','Complete 5 mini-games','game_master');
/*!40000 ALTER TABLE `badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `behavior`
--

DROP TABLE IF EXISTS `behavior`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `behavior` (
  `behavior_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `behavior_type` varchar(100) DEFAULT NULL,
  `behavior_details` text DEFAULT NULL,
  `indicator` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`behavior_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `behavior_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `behavior_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `behavior`
--

LOCK TABLES `behavior` WRITE;
/*!40000 ALTER TABLE `behavior` DISABLE KEYS */;
INSERT INTO `behavior` VALUES (1,6,'milestone','Hops on one foot briefly','24-30 months'),(2,6,'milestone','Climbs well on playground equipment','24-30 months'),(3,6,'milestone','Throws ball overhand','24-30 months'),(4,6,'milestone','Pedals tricycle','24-36 months'),(5,6,'milestone','Balances on one foot 2-3 seconds','30-36 months'),(6,6,'milestone','Draws circles and lines','24-30 months'),(7,6,'milestone','Uses child-safe scissors','24-30 months'),(8,6,'milestone','Copies simple letters','30-36 months'),(9,6,'milestone','Builds tower of 6+ blocks','24-30 months'),(10,6,'milestone','Buttons large buttons','30-36 months'),(11,7,'milestone','Focuses on activity 8-10 minutes','24-30 months'),(12,7,'milestone','Follows two-step directions','24-30 months'),(13,7,'milestone','Waits turn with prompting','27-33 months'),(14,7,'milestone','Completes 10+ piece puzzles','30-36 months'),(15,7,'milestone','Listens to songs all the way through','24-30 months'),(16,8,'milestone','Uses 2-3 word sentences','24-30 months'),(17,8,'milestone','Asks what/where questions','24-30 months'),(18,8,'milestone','Uses pronouns (I, me, you)','24-30 months'),(19,8,'milestone','Speech is 50-75% understandable','30-36 months'),(20,8,'milestone','Knows 200+ words','30-36 months'),(21,9,'milestone','Engages in cooperative play','24-30 months'),(22,9,'milestone','Takes turns with assistance','24-30 months'),(23,9,'milestone','Shows imaginative play','27-33 months'),(24,9,'milestone','Understands sharing concept','30-36 months'),(25,9,'milestone','Expresses emotions verbally','30-36 months'),(26,6,'milestone','Crawls on hands and knees','7-9 months'),(27,6,'milestone','Pulls to standing position','8-10 months'),(28,6,'milestone','Cruises along furniture','9-11 months'),(29,6,'milestone','Stands alone momentarily','10-12 months'),(30,6,'milestone','Uses pincer grasp thumb and finger','8-10 months'),(31,6,'milestone','Points with index finger','9-11 months'),(32,6,'milestone','Drops objects intentionally','8-10 months'),(33,6,'milestone','Claps hands together','9-10 months'),(34,7,'milestone','Attends to book for 2-3 minutes','8-10 months'),(35,7,'milestone','Responds to simple requests','9-11 months'),(36,7,'milestone','Watches caregiver during activities','9-11 months'),(37,7,'milestone','Shows joint attention (looks where you point)','9-12 months'),(38,8,'milestone','Uses mama/dada specifically','8-10 months'),(39,8,'milestone','Points to communicate needs','9-11 months'),(40,8,'milestone','Imitates gestures (wave bye-bye)','9-11 months'),(41,8,'milestone','Understands no','9-12 months'),(42,9,'milestone','Shows stranger anxiety','8-10 months'),(43,9,'milestone','Plays interactive games','9-11 months'),(44,9,'milestone','Shows preferences for people','9-11 months'),(45,9,'milestone','May cry when parent leaves','10-12 months'),(46,6,'milestone','Walks independently','12-15 months'),(47,6,'milestone','Squats to pick up toys','13-15 months'),(48,6,'milestone','Begins to run stiffly','15-18 months'),(49,6,'milestone','Walks up stairs with help','15-18 months'),(50,6,'milestone','Stacks 2-3 blocks','12-15 months'),(51,6,'milestone','Turns pages of board book','12-15 months'),(52,6,'milestone','Scribbles with crayon','13-15 months'),(53,6,'milestone','Uses spoon with some spilling','15-18 months'),(54,7,'milestone','Listens to short stories','12-15 months'),(55,7,'milestone','Focuses on activity for 3-5 minutes','13-15 months'),(56,7,'milestone','Follows one-step directions','13-15 months'),(57,7,'milestone','Notices when environment changes','15-18 months'),(58,8,'milestone','Uses 3-10 words meaningfully','12-15 months'),(59,8,'milestone','Follows simple directions','13-15 months'),(60,8,'milestone','Shakes head yes/no','13-15 months'),(61,8,'milestone','Points to show you something','15-18 months'),(62,9,'milestone','Shows affection to familiar people','12-15 months'),(63,9,'milestone','Imitates adult actions','13-15 months'),(64,9,'milestone','Plays alongside other children','13-15 months'),(65,9,'milestone','Shows empathy (cries when others cry)','15-18 months'),(66,10,'milestone','Hops on one foot briefly','24-30 months'),(67,10,'milestone','Climbs well on playground equipment','24-30 months'),(68,10,'milestone','Throws ball overhand','24-30 months'),(69,10,'milestone','Pedals tricycle','24-36 months'),(70,10,'milestone','Balances on one foot 2-3 seconds','30-36 months'),(71,11,'milestone','Draws circles and lines','24-30 months'),(72,11,'milestone','Uses child-safe scissors','24-30 months'),(73,11,'milestone','Copies simple letters','30-36 months'),(74,11,'milestone','Builds tower of 6+ blocks','24-30 months'),(75,11,'milestone','Buttons large buttons','30-36 months'),(76,10,'milestone','Walks independently','12-15 months'),(77,10,'milestone','Squats to pick up toys','13-15 months'),(78,10,'milestone','Begins to run stiffly','15-18 months'),(79,10,'milestone','Walks up stairs with help','15-18 months'),(80,11,'milestone','Stacks 2-3 blocks','12-15 months'),(81,11,'milestone','Turns pages of board book','12-15 months'),(82,11,'milestone','Scribbles with crayon','13-15 months'),(83,11,'milestone','Uses spoon with some spilling','15-18 months'),(84,10,'milestone','Lifts head when on tummy','0-2 months'),(85,10,'milestone','Pushes up on arms during tummy time','2-3 months'),(86,10,'milestone','Holds head steady without support','3-4 months'),(87,10,'milestone','Rolls from tummy to back','3-4 months'),(88,10,'milestone','Rolls over both ways','4-6 months'),(89,10,'milestone','Sits without support','5-7 months'),(90,10,'milestone','Bears weight on legs when held','5-7 months'),(91,10,'milestone','Begins to crawl','6-8 months'),(92,10,'milestone','Crawls on hands and knees','7-9 months'),(93,10,'milestone','Pulls to standing position','8-10 months'),(94,10,'milestone','Cruises along furniture','9-11 months'),(95,10,'milestone','Stands alone momentarily','10-12 months'),(96,10,'milestone','Runs with good coordination','18-21 months'),(97,10,'milestone','Kicks a ball forward','18-22 months'),(98,10,'milestone','Jumps with both feet','20-24 months'),(99,10,'milestone','Walks up stairs holding rail','18-24 months'),(100,11,'milestone','Opens and shuts hands','0-2 months'),(101,11,'milestone','Brings hands to mouth','1-3 months'),(102,11,'milestone','Grasps objects placed in hand','2-4 months'),(103,11,'milestone','Reaches for toys','3-4 months'),(104,11,'milestone','Transfers objects between hands','4-6 months'),(105,11,'milestone','Rakes at small objects','4-5 months'),(106,11,'milestone','Bangs objects together','6-7 months'),(107,11,'milestone','Uses raking grasp','5-6 months'),(108,11,'milestone','Uses pincer grasp thumb and finger','8-10 months'),(109,11,'milestone','Points with index finger','9-11 months'),(110,11,'milestone','Drops objects intentionally','8-10 months'),(111,11,'milestone','Claps hands together','9-10 months'),(112,11,'milestone','Stacks 4-6 blocks','18-21 months'),(113,11,'milestone','Turns doorknobs','18-22 months'),(114,11,'milestone','Strings large beads','20-24 months'),(115,11,'milestone','Uses spoon with minimal spilling','21-24 months'),(116,7,'milestone','Looks at faces when feeding','0-2 months'),(117,7,'milestone','Maintains eye contact briefly','1-3 months'),(118,7,'milestone','Watches moving objects','2-3 months'),(119,7,'milestone','Turns toward familiar voices','2-4 months'),(120,7,'milestone','Focuses on toy for 1-2 minutes','4-6 months'),(121,7,'milestone','Looks when name is called','5-7 months'),(122,7,'milestone','Pays attention to surroundings','6-8 months'),(123,7,'milestone','Shows preference for familiar people','6-8 months'),(124,7,'milestone','Attends to preferred activity 5-8 minutes','18-21 months'),(125,7,'milestone','Listens to entire short book','18-22 months'),(126,7,'milestone','Waits briefly for turn','20-24 months'),(127,7,'milestone','Completes simple puzzles','20-24 months'),(128,8,'milestone','Makes eye contact during feeding','0-2 months'),(129,8,'milestone','Coos and makes vowel sounds','1-3 months'),(130,8,'milestone','Smiles socially','2-3 months'),(131,8,'milestone','Responds to caregiver voice','2-4 months'),(132,8,'milestone','Babbles with consonants','4-6 months'),(133,8,'milestone','Responds to own name','5-7 months'),(134,8,'milestone','Uses sounds to get attention','6-8 months'),(135,8,'milestone','Imitates sounds','6-8 months'),(136,8,'milestone','Uses 10-50 words','18-21 months'),(137,8,'milestone','Combines two words','18-22 months'),(138,8,'milestone','Names familiar objects','18-22 months'),(139,8,'milestone','Follows two-step related directions','21-24 months'),(140,9,'milestone','Recognizes primary caregiver','0-2 months'),(141,9,'milestone','Smiles in response to smiles','2-3 months'),(142,9,'milestone','Enjoys being held','2-4 months'),(143,9,'milestone','Calms when picked up','3-4 months'),(144,9,'milestone','Distinguishes familiar vs strangers','4-6 months'),(145,9,'milestone','Enjoys social games (peek-a-boo)','5-7 months'),(146,9,'milestone','Shows excitement around others','6-8 months'),(147,9,'milestone','Reaches to be picked up','6-8 months'),(148,9,'milestone','Shows parallel play','18-21 months'),(149,9,'milestone','Shows ownership (mine!)','18-22 months'),(150,9,'milestone','Helps with simple tasks','18-22 months'),(151,9,'milestone','Shows concern for others','21-24 months');
/*!40000 ALTER TABLE `behavior` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `behavior_category`
--

DROP TABLE IF EXISTS `behavior_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `behavior_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) DEFAULT NULL,
  `category_type` varchar(100) DEFAULT NULL,
  `category_description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `behavior_category`
--

LOCK TABLES `behavior_category` WRITE;
/*!40000 ALTER TABLE `behavior_category` DISABLE KEYS */;
INSERT INTO `behavior_category` VALUES (1,'Motor Development','Physical','Tracks gross and fine motor skill progression'),(2,'Speech and Language','Communication','Tracks verbal and non-verbal communication skills'),(3,'Social Interaction','Social-Emotional','Tracks social behavior and emotional development'),(4,'Cognitive Skills','Cognitive','Tracks problem-solving and learning abilities'),(5,'Self-Care','Adaptive','Tracks self-care and daily living skills'),(6,'🦵 Motor Skills','motor_skills','Physical development including gross motor (walking, running, balance) and fine motor (grasping, drawing, coordination)'),(7,'🧠 Attention','attention','Focus, concentration, and ability to sustain attention on activities and follow directions'),(8,'💬 Communication','communication','Language development including verbal expression, understanding, and non-verbal communication'),(9,'🤝 Social Skills','social_skills','Social interaction, emotional development, play skills, and relationship building'),(10,'⚡ Gross Motor','gross_motor','Physical development involving large muscle movements like walking, running, and balance'),(11,'🤏 Fine Motor','fine_motor','Physical development involving small muscle movements like grasping, drawing, and coordination');
/*!40000 ALTER TABLE `behavior_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL COMMENT 'admin user_id',
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL COMMENT 'NULL = permanent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_ips`
--

LOCK TABLES `blocked_ips` WRITE;
/*!40000 ALTER TABLE `blocked_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child`
--

DROP TABLE IF EXISTS `child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `child_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child`
--

LOCK TABLES `child` WRITE;
/*!40000 ALTER TABLE `child` DISABLE KEYS */;
INSERT INTO `child` VALUES ('12345000000001',1,76,'Omar','Ali',14,5,2020,'male',NULL),('12345000000002',2,77,'Habiba','Family',16,7,2018,'female',NULL),('12345000000003',3,78,'Ahmed','Family',8,3,2019,'male',NULL),('12345000000004',4,78,'Youssef','Family',7,7,2018,'male',NULL),('12345000000005',5,79,'Dina','Family',10,11,2021,'female',NULL),('12345000000006',6,80,'Ibrahim','Family',28,4,2018,'male',NULL),('12345000000007',7,80,'Amr','Family',25,1,2023,'male',NULL),('12345000000008',8,81,'Noha','Family',6,12,2018,'female',NULL),('12345000000009',9,81,'Ahmed','Family',15,1,2021,'male',NULL),('12345000000010',10,82,'Yasmin','Family',25,4,2022,'female',NULL),('12345000000011',11,83,'Mohamed','Family',20,8,2022,'male',NULL),('12345000000012',12,83,'Mona','Family',1,7,2022,'female',NULL),('12345000000013',13,84,'Mariam','Family',23,5,2019,'female',NULL),('12345000000014',14,84,'Ahmed','Family',25,11,2022,'male',NULL),('12345000000015',15,85,'Farida','Family',14,11,2023,'female',NULL),('12345000000016',16,85,'Laila','Family',4,11,2018,'female',NULL),('12345000000017',17,86,'Adam','Family',16,5,2020,'male',NULL),('12345000000018',18,86,'Yassin','Family',24,8,2020,'male',NULL),('12345000000019',19,87,'Heba','Family',7,5,2018,'female',NULL),('12345000000020',20,87,'Dina','Family',7,4,2019,'female',NULL),('12345000000021',21,88,'Marwan','Family',26,12,2020,'male',NULL),('12345000000022',22,89,'Seif','Family',3,10,2018,'male',NULL),('12345000000023',23,90,'Reem','Family',22,8,2023,'female',NULL),('12345000000024',24,90,'Hussein','Family',27,7,2018,'male',NULL),('12345000000025',25,91,'Mostafa','Family',28,2,2021,'male',NULL),('12345000000026',26,91,'Reem','Family',6,1,2020,'female',NULL),('12345000000027',27,92,'Laila','Family',28,2,2019,'female',NULL),('12345000000028',28,93,'Tarek','Family',22,2,2023,'male',NULL),('12345000000029',29,93,'Amr','Family',10,5,2019,'male',NULL),('12345000000030',30,94,'Aya','Family',22,12,2021,'female',NULL),('12345000000031',31,94,'Rana','Family',10,6,2021,'female',NULL),('12345000000032',32,95,'Ziad','Family',27,6,2021,'male',NULL),('12345000000033',33,95,'Youssef','Family',27,3,2020,'male',NULL),('12345000000034',34,96,'Hassan','Family',21,4,2022,'male',NULL),('12345000000035',35,96,'Sara','Family',19,1,2018,'female',NULL),('12345000000036',36,97,'Mariam','Family',14,5,2018,'female',NULL),('12345000000037',37,97,'Mostafa','Family',12,5,2021,'male',NULL),('12345000000038',38,98,'Khaled','Family',9,10,2023,'male',NULL),('12345000000039',39,98,'Maha','Family',17,4,2019,'female',NULL),('12345000000040',40,99,'Yasmin','Family',10,6,2020,'female',NULL),('12345000000041',41,99,'Salma','Family',12,1,2022,'female',NULL),('12345000000042',42,100,'Farida','Family',6,4,2018,'female',NULL),('12345000000043',43,101,'Hussein','Family',27,7,2019,'male',NULL),('12345000000044',44,102,'Fatma','Family',18,12,2023,'female',NULL),('12345000000045',45,102,'Ibrahim','Family',14,9,2023,'male',NULL),('12345000000046',46,103,'Reem','Family',5,11,2021,'female',NULL),('12345000000047',47,103,'Ziad','Family',15,6,2023,'male',NULL),('12345000000048',48,104,'Hussein','Family',20,9,2021,'male',NULL),('12345000000049',49,104,'Maha','Family',8,6,2023,'female',NULL),('12345000000050',50,105,'Farida','Family',17,7,2022,'female',NULL),('12345000000051',51,105,'Ali','Family',27,9,2020,'male',NULL),('12345000000052',52,106,'Adam','Family',22,12,2022,'male',NULL),('12345000000053',53,106,'Nada','Family',7,12,2021,'female',NULL),('12345000000054',54,107,'Farida','Family',25,3,2020,'female',NULL),('12345000000055',55,107,'Laila','Family',13,8,2021,'female',NULL),('12345000000056',56,108,'Hamza','Family',16,1,2020,'male',NULL),('12345000000057',57,108,'Fatma','Family',22,1,2020,'female',NULL),('12345000000058',58,109,'Hamza','Family',1,3,2023,'male',NULL),('12345000000059',59,109,'Hassan','Family',6,7,2020,'male',NULL),('12345000000060',60,110,'Maha','Family',10,3,2020,'female',NULL),('12345000000061',61,111,'Hussein','Family',26,4,2020,'male',NULL),('12345000000062',62,112,'Reem','Family',17,4,2020,'female',NULL),('12345000000063',63,112,'Seif','Family',17,2,2020,'male',NULL),('12345000000064',64,113,'Mostafa','Family',9,9,2018,'male',NULL),('12345000000065',65,113,'Mostafa','Family',25,11,2018,'male',NULL),('12345000000066',66,114,'Hana','Family',4,8,2023,'female',NULL),('12345000000067',67,115,'Sara','Family',15,9,2021,'female',NULL),('12345000000068',68,115,'Marwan','Family',26,8,2020,'male',NULL),('12345000000069',69,116,'Tarek','Family',28,4,2023,'male',NULL),('12345000000070',70,117,'Tarek','Family',17,8,2023,'male',NULL),('12345000000071',71,117,'Nada','Family',3,6,2019,'female',NULL),('12345000000072',72,118,'Omar','Family',28,12,2023,'male',NULL),('12345000000073',73,118,'Hassan','Family',9,11,2018,'male',NULL),('12345000000074',74,119,'Youssef','Family',9,9,2023,'male',NULL),('12345000000075',75,119,'Ahmed','Family',3,6,2018,'male',NULL),('12345000000076',76,120,'Mona','Family',6,10,2022,'female',NULL),('12345000000077',77,120,'Maha','Family',27,5,2018,'female',NULL),('12345000000078',78,121,'Ziad','Family',11,3,2020,'male',NULL),('12345000000079',79,122,'Seif','Family',9,10,2018,'male',NULL),('12345000000080',80,122,'Tarek','Family',28,12,2022,'male',NULL),('12345000000081',81,123,'Dina','Family',5,4,2023,'female',NULL),('12345000000082',82,123,'Adam','Family',16,12,2020,'male',NULL),('12345000000083',83,124,'Sara','Family',10,2,2023,'female',NULL),('12345000000084',84,125,'Ahmed','Family',4,6,2019,'male',NULL),('12345000000085',85,125,'Ibrahim','Family',20,7,2018,'male',NULL),('12345000000086',86,126,'Youssef','Family',9,7,2023,'male',NULL),('12345000000087',87,126,'Kareem','Family',20,7,2019,'male',NULL),('12345000000088',88,127,'Ahmed','Family',9,4,2018,'male',NULL),('12345000000089',89,128,'Farida','Family',4,8,2018,'female',NULL),('12345000000090',90,128,'Habiba','Family',16,9,2018,'female',NULL),('12345000000091',91,129,'Farida','Family',25,8,2022,'female',NULL),('12345000000092',92,129,'Omar','Family',9,8,2022,'male',NULL),('12345000000093',93,130,'Nour','Family',25,12,2020,'female',NULL),('12345000000094',94,131,'Rana','Family',17,5,2018,'female',NULL),('12345000000095',95,131,'Rana','Family',15,11,2019,'female',NULL),('12345000000096',96,132,'Habiba','Family',15,6,2022,'female',NULL),('12345000000097',97,133,'Hussein','Family',1,12,2020,'male',NULL),('12345000000098',98,134,'Ahmed','Family',4,7,2022,'male',NULL),('12345000000099',99,135,'Sara','Family',15,3,2022,'female',NULL),('12345000000100',100,135,'Rana','Family',10,9,2023,'female',NULL),('12345000000101',101,136,'Youssef','Family',15,6,2020,'male',NULL),('12345000000102',102,136,'Maha','Family',21,2,2018,'female',NULL),('12345000000103',103,137,'Marwan','Family',19,2,2018,'male',NULL),('12345000000104',104,137,'Ibrahim','Family',22,3,2020,'male',NULL),('12345000000105',105,138,'Yasmin','Family',13,4,2020,'female',NULL),('12345000000106',106,138,'Omar','Family',19,10,2023,'male',NULL),('12345000000107',107,139,'Habiba','Family',24,2,2021,'female',NULL),('12345000000108',108,139,'Hassan','Family',25,2,2020,'male',NULL),('12345000000109',109,140,'Mariam','Family',14,4,2018,'female',NULL),('12345000000110',110,141,'Heba','Family',1,12,2018,'female',NULL),('12345000000111',111,142,'Reem','Family',4,11,2022,'female',NULL),('12345000000112',112,143,'Marwan','Family',1,1,2019,'male',NULL),('12345000000113',113,143,'Tarek','Family',19,10,2021,'male',NULL),('12345000000114',114,144,'Laila','Family',7,11,2023,'female',NULL),('12345000000115',115,144,'Maha','Family',4,12,2020,'female',NULL),('12345000000116',116,145,'Heba','Family',16,2,2019,'female',NULL),('12345000000117',117,145,'Mohamed','Family',21,3,2018,'male',NULL),('12345000000118',118,146,'Yasmin','Family',16,11,2022,'female',NULL),('12345000000119',119,146,'Hana','Family',24,5,2021,'female',NULL),('12345000000120',120,147,'Jana','Family',22,9,2020,'female',NULL),('12345000000121',121,148,'Aya','Family',22,5,2020,'female',NULL),('12345000000122',122,149,'Hassan','Family',3,8,2018,'male',NULL),('12345000000123',123,149,'Rana','Family',20,12,2022,'female',NULL),('12345000000124',124,150,'Habiba','Family',8,6,2019,'female',NULL),('12345000000125',125,151,'Hussein','Family',6,3,2019,'male',NULL),('12345000000126',126,151,'Omar','Family',14,7,2020,'male',NULL),('12345000000127',127,152,'Adam','Family',3,12,2022,'male',NULL),('12345000000128',128,152,'Ziad','Family',11,9,2023,'male',NULL),('12345000000129',129,153,'Fatma','Family',8,1,2018,'female',NULL),('12345000000130',130,153,'Yasmin','Family',17,3,2023,'female',NULL),('12345000000131',131,154,'Salma','Family',11,10,2023,'female',NULL),('12345000000132',132,155,'Noha','Family',5,9,2018,'female',NULL),('12345000000133',133,156,'Omar','Family',6,8,2021,'male',NULL),('12345000000134',134,156,'Laila','Family',15,2,2023,'female',NULL),('12345000000135',135,157,'Yasmin','Family',11,6,2023,'female',NULL),('12345000000136',136,158,'Tarek','Family',14,7,2019,'male',NULL),('12345000000137',137,158,'Hussein','Family',5,10,2022,'male',NULL),('12345000000138',138,159,'Omar','Family',9,1,2023,'male',NULL),('12345000000139',139,159,'Yasmin','Family',25,6,2020,'female',NULL),('12345000000140',140,160,'Yasmin','Family',25,6,2019,'female',NULL),('12345000000141',141,160,'Sara','Family',25,7,2022,'female',NULL),('12345000000142',142,161,'Nada','Family',12,2,2020,'female',NULL),('12345000000143',143,162,'Hassan','Family',16,4,2021,'male',NULL),('12345000000144',144,163,'Kareem','Family',24,2,2022,'male',NULL),('12345000000145',145,164,'Youssef','Family',13,10,2022,'male',NULL),('12345000000146',146,164,'Khaled','Family',15,9,2021,'male',NULL),('12345000000147',147,165,'Salma','Family',1,12,2019,'female',NULL),('12345000000148',148,166,'Amr','Family',9,11,2023,'male',NULL),('12345000000149',149,167,'Heba','Family',7,12,2019,'female',NULL),('12345000000150',150,168,'Amr','Family',6,5,2019,'male',NULL),('12345000000151',151,168,'Habiba','Family',14,12,2021,'female',NULL),('12345000000152',152,169,'Jana','Family',8,11,2022,'female',NULL),('12345000000153',153,169,'Fatma','Family',19,12,2022,'female',NULL),('12345000000154',154,170,'Marwan','Family',1,4,2018,'male',NULL),('12345000000155',155,171,'Farida','Family',3,3,2022,'female',NULL),('12345000000156',156,171,'Nada','Family',3,1,2021,'female',NULL),('12345000000157',157,172,'Farida','Family',21,3,2021,'female',NULL),('12345000000158',158,173,'Kareem','Family',26,2,2019,'male',NULL),('12345000000159',159,173,'Heba','Family',11,10,2019,'female',NULL),('12345000000160',160,174,'Maha','Family',23,10,2018,'female',NULL),('12345000000161',161,175,'Ahmed','Family',1,2,2019,'male',NULL),('12345000000162',162,175,'Laila','Family',9,5,2021,'female',NULL),('12345000000163',163,176,'Yasmin','Family',22,3,2018,'female',NULL),('12345000000164',164,176,'Yasmin','Family',17,10,2023,'female',NULL),('12345000000165',165,177,'Marwan','Family',5,7,2021,'male',NULL),('12345000000166',166,177,'Yassin','Family',19,5,2019,'male',NULL),('12345000000167',167,178,'Marwan','Family',27,11,2020,'male',NULL),('12345000000168',168,178,'Mostafa','Family',15,2,2020,'male',NULL),('12345000000169',169,179,'Yasmin','Family',1,6,2019,'female',NULL),('12345000000170',170,179,'Yassin','Family',19,9,2023,'male',NULL),('12345000000171',171,180,'Khaled','Family',5,4,2023,'male',NULL),('12345000000172',172,180,'Adam','Family',9,7,2018,'male',NULL),('12345000000173',173,181,'Mahmoud','Family',11,3,2021,'male',NULL),('12345000000174',174,181,'Mostafa','Family',11,8,2023,'male',NULL),('12345000000175',175,182,'Fatma','Family',26,12,2020,'female',NULL),('12345000000176',176,182,'Noha','Family',8,8,2020,'female',NULL),('12345000000177',177,183,'Mahmoud','Family',24,4,2019,'male',NULL),('12345000000178',178,184,'Habiba','Family',25,3,2023,'female',NULL),('12345000000179',179,185,'Salma','Family',19,8,2022,'female',NULL),('12345000000180',180,185,'Seif','Family',2,4,2023,'male',NULL),('12345000000181',181,186,'Ali','Family',8,4,2020,'male',NULL),('12345000000182',182,187,'Mohamed','Family',22,3,2020,'male',NULL),('12345000000183',183,188,'Hamza','Family',25,2,2018,'male',NULL),('12345000000184',184,189,'Farida','Family',2,12,2020,'female',NULL),('12345000000185',185,190,'Ahmed','Family',13,10,2023,'male',NULL),('12345000000186',186,191,'Hamza','Family',15,9,2018,'male',NULL),('12345000000187',187,192,'Tarek','Family',18,12,2020,'male',NULL),('12345000000188',188,193,'Ibrahim','Family',20,5,2023,'male',NULL),('12345000000189',189,193,'Adam','Family',18,7,2023,'male',NULL),('12345000000190',190,194,'Laila','Family',23,3,2020,'female',NULL),('12345000000191',191,194,'Ahmed','Family',6,8,2023,'male',NULL),('12345000000192',192,195,'Ahmed','Family',2,10,2019,'male',NULL),('12345000000193',193,195,'Mahmoud','Family',17,1,2022,'male',NULL),('12345000000194',194,196,'Sara','Family',28,9,2021,'female',NULL);
/*!40000 ALTER TABLE `child` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_child_insert` AFTER INSERT ON `child` FOR EACH ROW BEGIN
    UPDATE parent
    SET number_of_children = number_of_children + 1
    WHERE parent_id = NEW.parent_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `child_activities`
--

DROP TABLE IF EXISTS `child_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_activities` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_activities`
--

LOCK TABLES `child_activities` WRITE;
/*!40000 ALTER TABLE `child_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `child_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child_badge`
--

DROP TABLE IF EXISTS `child_badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_badge` (
  `child_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`child_id`,`badge_id`),
  KEY `badge_id` (`badge_id`),
  CONSTRAINT `child_badge_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`),
  CONSTRAINT `child_badge_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badge` (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_badge`
--

LOCK TABLES `child_badge` WRITE;
/*!40000 ALTER TABLE `child_badge` DISABLE KEYS */;
INSERT INTO `child_badge` VALUES (1,1,'2026-05-04 00:32:43'),(1,2,'2026-05-04 00:45:37'),(1,4,'2026-05-03 22:32:38'),(1,6,'2026-06-14 13:10:13'),(1,19,'2026-05-22 18:07:08'),(1,21,'2026-06-14 18:50:53'),(1,23,'2026-05-04 00:29:40'),(3,1,'2026-06-12 15:57:02'),(3,2,'2026-06-14 19:06:07'),(3,4,'2026-05-04 00:46:30'),(3,19,'2026-06-12 15:57:02'),(3,22,'2026-05-04 00:31:32'),(3,23,'2026-05-04 00:31:32'),(5,1,'2026-06-12 15:53:35'),(11,1,'2026-06-11 21:54:37'),(11,4,'2026-05-23 02:12:44'),(12,1,'2026-05-23 12:57:31'),(12,4,'2026-05-23 19:03:19'),(13,1,'2026-05-24 05:40:33'),(13,4,'2026-05-24 05:40:54'),(13,19,'2026-05-24 05:40:33'),(14,4,'2026-06-11 10:32:34');
/*!40000 ALTER TABLE `child_badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child_exhibited_behavior`
--

DROP TABLE IF EXISTS `child_exhibited_behavior`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_exhibited_behavior` (
  `child_id` int(11) NOT NULL,
  `behavior_id` int(11) NOT NULL,
  `frequency` int(11) DEFAULT NULL,
  `severity` varchar(100) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`child_id`,`behavior_id`),
  KEY `behavior_id` (`behavior_id`),
  CONSTRAINT `child_exhibited_behavior_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`),
  CONSTRAINT `child_exhibited_behavior_ibfk_2` FOREIGN KEY (`behavior_id`) REFERENCES `behavior` (`behavior_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_exhibited_behavior`
--

LOCK TABLES `child_exhibited_behavior` WRITE;
/*!40000 ALTER TABLE `child_exhibited_behavior` DISABLE KEYS */;
INSERT INTO `child_exhibited_behavior` VALUES (1,11,2,'mild','2026-05-22 23:56:00'),(1,12,2,'mild','2026-05-22 23:56:00'),(1,13,2,'mild','2026-05-22 23:56:00'),(1,14,3,'severe','2026-05-22 23:56:00'),(1,15,2,'mild','2026-05-22 23:56:00'),(1,17,2,'mild','2026-05-22 23:56:00'),(1,19,2,'mild','2026-05-22 23:56:00'),(1,20,2,'mild','2026-05-22 23:56:00'),(1,34,2,'mild','2026-05-22 23:56:00'),(13,11,2,'mild','2026-05-24 05:43:12'),(13,12,2,'mild','2026-05-24 05:43:12'),(13,13,2,'mild','2026-05-24 05:43:12'),(13,14,2,'mild','2026-05-24 05:43:12'),(13,15,2,'mild','2026-05-24 05:43:12'),(13,16,2,'mild','2026-05-24 05:43:12'),(13,17,2,'mild','2026-05-24 05:43:12'),(13,19,2,'mild','2026-05-24 05:43:12'),(13,20,2,'mild','2026-05-24 05:43:12'),(13,34,2,'mild','2026-05-24 05:43:12'),(13,35,2,'mild','2026-05-24 05:43:12'),(13,37,2,'mild','2026-05-24 05:43:12'),(13,39,2,'mild','2026-05-24 05:43:12'),(13,40,2,'mild','2026-05-24 05:43:12'),(13,41,2,'mild','2026-05-24 05:43:12'),(13,54,2,'mild','2026-05-24 05:43:12'),(13,55,2,'mild','2026-05-24 05:43:12'),(13,56,2,'mild','2026-05-24 05:43:12'),(13,57,2,'mild','2026-05-24 05:43:12'),(13,59,2,'mild','2026-05-24 05:43:12'),(13,60,2,'mild','2026-05-24 05:43:12'),(13,61,2,'mild','2026-05-24 05:43:12');
/*!40000 ALTER TABLE `child_exhibited_behavior` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child_generated_system_report`
--

DROP TABLE IF EXISTS `child_generated_system_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_generated_system_report` (
  `child_id` int(11) NOT NULL,
  `report` varchar(255) NOT NULL,
  PRIMARY KEY (`child_id`,`report`),
  CONSTRAINT `child_generated_system_report_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_generated_system_report`
--

LOCK TABLES `child_generated_system_report` WRITE;
/*!40000 ALTER TABLE `child_generated_system_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `child_generated_system_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child_last_login`
--

DROP TABLE IF EXISTS `child_last_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_last_login` (
  `child_id` int(11) NOT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`child_id`,`login_at`),
  CONSTRAINT `child_last_login_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_last_login`
--

LOCK TABLES `child_last_login` WRITE;
/*!40000 ALTER TABLE `child_last_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `child_last_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `child_milestones`
--

DROP TABLE IF EXISTS `child_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child_milestones` (
  `child_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `achieved_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`child_id`,`milestone_id`),
  KEY `milestone_id` (`milestone_id`),
  CONSTRAINT `child_milestones_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE,
  CONSTRAINT `child_milestones_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`milestone_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child_milestones`
--

LOCK TABLES `child_milestones` WRITE;
/*!40000 ALTER TABLE `child_milestones` DISABLE KEYS */;
/*!40000 ALTER TABLE `child_milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic`
--

DROP TABLE IF EXISTS `clinic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic` (
  `clinic_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `opening_hours` varchar(255) DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rating` decimal(3,2) DEFAULT 0.00,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `medical_specialties` text DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT 1,
  `branches` text DEFAULT NULL,
  PRIMARY KEY (`clinic_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `clinic_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic`
--

LOCK TABLES `clinic` WRITE;
/*!40000 ALTER TABLE `clinic` DISABLE KEYS */;
INSERT INTO `clinic` VALUES (2,0,'Al Amal Therapy Center',NULL,NULL,NULL,'Maadi, Cairo',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.80,'2026-06-15 13:47:20',NULL,1,NULL),(3,0,'Al Amal Kids Clinic',NULL,NULL,NULL,'Smouha, Alexandria',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.40,'2026-06-15 13:47:20',NULL,1,NULL),(4,0,'Al Nada Rehab Center',NULL,NULL,NULL,'Sheikh Zayed, Giza',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.20,'2026-06-15 13:47:20',NULL,1,NULL),(5,0,'Al Shifa Medical Hub',NULL,NULL,NULL,'Smouha, Alexandria',NULL,NULL,NULL,NULL,NULL,NULL,'pending',4.70,'2026-06-15 13:47:20',NULL,1,NULL),(6,0,'Al Shifa Care Center',NULL,NULL,NULL,'Mansoura, Dakahlia',NULL,NULL,NULL,NULL,NULL,NULL,'suspended',4.90,'2026-06-15 13:47:20',NULL,1,NULL),(7,0,'Nour Polyclinic',NULL,NULL,NULL,'Gleem, Alexandria',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.00,'2026-06-15 13:47:20',NULL,1,NULL),(8,0,'Bright Polyclinic',NULL,NULL,NULL,'October, Giza',NULL,NULL,NULL,NULL,NULL,NULL,'pending',3.10,'2026-06-15 13:47:20',NULL,1,NULL),(9,0,'Hope Rehab Center',NULL,NULL,NULL,'Zamalek, Cairo',NULL,NULL,NULL,NULL,NULL,NULL,'approved',3.50,'2026-06-15 13:47:20',NULL,1,NULL),(10,0,'Cairo Medical Hub',NULL,NULL,NULL,'Gleem, Alexandria',NULL,NULL,NULL,NULL,NULL,NULL,'approved',3.70,'2026-06-15 13:47:20',NULL,1,NULL),(11,0,'Al Hoda Medical Hub',NULL,NULL,NULL,'Nasr City, Cairo',NULL,NULL,NULL,NULL,NULL,NULL,'approved',3.10,'2026-06-15 13:47:20',NULL,1,NULL),(12,0,'Cairo Rehab Center',NULL,NULL,NULL,'Mansoura, Dakahlia',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.60,'2026-06-15 13:47:20',NULL,1,NULL),(13,0,'Nour Kids Clinic',NULL,NULL,NULL,'Mansoura, Dakahlia',NULL,NULL,NULL,NULL,NULL,NULL,'approved',3.50,'2026-06-15 13:47:20',NULL,1,NULL),(14,0,'Care Medical Hub',NULL,NULL,NULL,'Heliopolis, Cairo',NULL,NULL,NULL,NULL,NULL,NULL,'approved',4.20,'2026-06-15 13:47:20',NULL,1,NULL);
/*!40000 ALTER TABLE `clinic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_branches`
--

DROP TABLE IF EXISTS `clinic_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clinic_id` int(11) NOT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `detailed_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `building` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_branches`
--

LOCK TABLES `clinic_branches` WRITE;
/*!40000 ALTER TABLE `clinic_branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_phone`
--

DROP TABLE IF EXISTS `clinic_phone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_phone` (
  `clinic_id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  PRIMARY KEY (`clinic_id`,`phone`),
  CONSTRAINT `clinic_phone_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinic` (`clinic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_phone`
--

LOCK TABLES `clinic_phone` WRITE;
/*!40000 ALTER TABLE `clinic_phone` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic_phone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_reviews`
--

DROP TABLE IF EXISTS `clinic_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `clinic_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `uq_clinic_review` (`parent_id`,`appointment_id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `parent_id` (`parent_id`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_reviews`
--

LOCK TABLES `clinic_reviews` WRITE;
/*!40000 ALTER TABLE `clinic_reviews` DISABLE KEYS */;
INSERT INTO `clinic_reviews` VALUES (1,2,76,0,5,'Very clean and professional therapy center.','2023-11-25 08:05:00');
/*!40000 ALTER TABLE `clinic_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_messages`
--

DROP TABLE IF EXISTS `community_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `likes` int(11) DEFAULT 0,
  `reply_count` int(11) DEFAULT 0,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `community_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_messages`
--

LOCK TABLES `community_messages` WRITE;
/*!40000 ALTER TABLE `community_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultation_messages`
--

DROP TABLE IF EXISTS `consultation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultation_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `consultation_id` (`consultation_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `consultation_messages_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultation_messages`
--

LOCK TABLES `consultation_messages` WRITE;
/*!40000 ALTER TABLE `consultation_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `consultation_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultations` (
  `consultation_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `specialist_id` int(11) NOT NULL,
  `child_id` int(11) DEFAULT NULL,
  `consultation_type` enum('video','voice','chat') DEFAULT 'video',
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`consultation_id`),
  KEY `parent_id` (`parent_id`),
  KEY `specialist_id` (`specialist_id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`specialist_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_ibfk_3` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_onboarding`
--

DROP TABLE IF EXISTS `doctor_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_onboarding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `certifications` varchar(255) DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `focus_areas` text DEFAULT NULL,
  `working_days` text DEFAULT NULL,
  `start_time` time DEFAULT '09:00:00',
  `end_time` time DEFAULT '17:00:00',
  `consultation_types` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `age_groups` text DEFAULT NULL,
  `therapy_approaches` text DEFAULT NULL,
  `session_duration` int(11) DEFAULT 30,
  `max_patients_per_day` int(11) DEFAULT 10,
  `follow_up_reminder` varchar(50) DEFAULT '1week',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_onboarding`
--

LOCK TABLES `doctor_onboarding` WRITE;
/*!40000 ALTER TABLE `doctor_onboarding` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_onboarding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_report`
--

DROP TABLE IF EXISTS `doctor_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_report` (
  `doctor_report_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `child_report` varchar(255) DEFAULT NULL COMMENT 'Reference to child_generated_system_report.report',
  `doctor_notes` text NOT NULL,
  `recommendations` text DEFAULT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visibility` enum('private','shared') DEFAULT 'private',
  PRIMARY KEY (`doctor_report_id`),
  KEY `idx_dr_specialist` (`specialist_id`),
  KEY `idx_dr_child` (`child_id`),
  CONSTRAINT `doctor_report_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_report_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_report`
--

LOCK TABLES `doctor_report` WRITE;
/*!40000 ALTER TABLE `doctor_report` DISABLE KEYS */;
INSERT INTO `doctor_report` VALUES (1,71,1,'Shared Report: full-report','sarah 3ayanaaaa w 3andha ekt2ab meen mashro3 el ta5rog','troo7 tesayf','2026-06-13','2026-06-13 17:27:06','private'),(2,71,1,'Shared Report: full-report','woowww','ehehehehe','2026-06-13','2026-06-13 17:51:37','private');
/*!40000 ALTER TABLE `doctor_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template_type` varchar(50) DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `recipient_email` (`recipient_email`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'admin@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:22:47'),(2,'sarah.j@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:22:50'),(3,'michael.t@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:22:53'),(4,'ahmed.parent@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:22:56'),(5,'sarah.m@citykids.com','test','admin_notification','sent',NULL,'2026-04-10 12:22:58'),(6,'ahmed.h@sunrisepeds.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:01'),(7,'layla.n@citykids.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:04'),(8,'mostafakabil123@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:07'),(9,'testparent@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:10'),(10,'mostafa@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:13'),(11,'browser_test@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:15'),(12,'testparent@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:18'),(13,'s.jenkins@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:21'),(14,'m.stone@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:24'),(15,'a.gomez@pediatric.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:27'),(16,'r.cheng@pediatric.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:30'),(17,'e.davis@sunny.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:33'),(18,'j.wilson@sunny.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:35'),(19,'o.martinez@thrive.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:38'),(20,'w.taylor@thrive.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:41'),(21,'224156@eru.edu.eg','test','admin_notification','sent',NULL,'2026-04-10 12:23:44'),(22,'test@test.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:47'),(23,'parent1@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:50'),(24,'jdoe@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:52'),(25,'admin@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:55'),(26,'sarah.j@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:23:58'),(27,'michael.t@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:01'),(28,'ahmed.parent@email.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:04'),(29,'sarah.m@citykids.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:07'),(30,'ahmed.h@sunrisepeds.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:09'),(31,'layla.n@citykids.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:12'),(32,'mostafakabil123@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:15'),(33,'testparent@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:18'),(34,'mostafa@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:20'),(35,'browser_test@example.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:23'),(36,'testparent@gmail.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:26'),(37,'s.jenkins@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:29'),(38,'m.stone@brightsteps.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:32'),(39,'a.gomez@pediatric.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:35'),(40,'r.cheng@pediatric.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:38'),(41,'e.davis@sunny.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:41'),(42,'j.wilson@sunny.com','test','admin_notification','sent',NULL,'2026-04-10 12:24:44');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `specialist_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `parent_id` (`parent_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flagged_content`
--

DROP TABLE IF EXISTS `flagged_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flagged_content` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flagged_content`
--

LOCK TABLES `flagged_content` WRITE;
/*!40000 ALTER TABLE `flagged_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `flagged_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `growth_record`
--

DROP TABLE IF EXISTS `growth_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `growth_record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `head_circumference` decimal(5,2) DEFAULT NULL,
  `arm_circumference` decimal(5,2) DEFAULT NULL,
  `subscapular_skinfold` decimal(5,2) DEFAULT NULL,
  `triceps_skinfold` decimal(5,2) DEFAULT NULL,
  `motor_milestones_score` int(11) DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `growth_record_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `growth_record`
--

LOCK TABLES `growth_record` WRITE;
/*!40000 ALTER TABLE `growth_record` DISABLE KEYS */;
INSERT INTO `growth_record` VALUES (1,3,151.00,30.00,23.00,NULL,NULL,NULL,0,'2026-04-04 23:42:33'),(2,1,76.00,10.50,45.00,NULL,NULL,NULL,0,'2026-04-05 00:01:40'),(3,4,85.00,12.00,48.00,NULL,NULL,NULL,0,'2026-04-05 00:33:30'),(4,3,60.00,NULL,NULL,NULL,NULL,NULL,0,'2026-04-05 02:56:04'),(5,3,90.00,15.00,50.00,NULL,NULL,NULL,0,'2026-04-05 03:17:25'),(6,3,90.00,15.00,55.00,NULL,NULL,NULL,0,'2026-04-05 03:19:19'),(7,3,90.00,20.00,55.00,NULL,NULL,NULL,0,'2026-04-05 03:20:00'),(8,3,80.00,20.00,55.00,NULL,NULL,NULL,0,'2026-04-05 08:47:00'),(9,1,76.00,12.00,45.00,NULL,NULL,NULL,0,'2026-04-05 10:17:13'),(10,3,80.00,50.00,55.00,NULL,NULL,NULL,0,'2026-04-05 10:31:40'),(11,7,85.00,12.00,48.00,NULL,NULL,NULL,0,'2026-04-07 05:13:29'),(12,7,85.00,12.50,48.00,NULL,NULL,NULL,0,'2026-04-07 05:14:45'),(13,8,999.99,10.00,45.00,NULL,NULL,NULL,0,'2026-04-07 05:53:24'),(14,8,999.99,12.00,45.00,NULL,NULL,NULL,0,'2026-04-07 05:54:03'),(15,1,76.00,12.00,30.00,NULL,NULL,NULL,0,'2026-04-16 16:01:43'),(16,1,76.00,12.00,50.00,NULL,NULL,NULL,0,'2026-04-16 16:02:00'),(17,10,53.00,10.00,20.00,NULL,NULL,NULL,0,'2026-05-03 09:37:17'),(19,11,76.00,10.00,45.00,NULL,NULL,NULL,0,'2026-05-23 01:43:00'),(22,1,96.00,16.00,51.00,NULL,NULL,NULL,0,'2026-05-23 02:38:03'),(23,12,76.00,10.00,45.00,NULL,NULL,NULL,0,'2026-05-23 19:03:18'),(24,13,76.00,10.00,45.00,NULL,NULL,NULL,0,'2026-05-24 05:40:53'),(25,14,30.00,5.00,15.00,NULL,NULL,NULL,0,'2026-06-11 10:32:32'),(26,1,20.00,10.00,30.00,NULL,NULL,NULL,0,'2026-06-11 22:49:41');
/*!40000 ALTER TABLE `growth_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `child_id` (`child_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_records`
--

LOCK TABLES `medical_records` WRITE;
/*!40000 ALTER TABLE `medical_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `child_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  `message_type` varchar(50) DEFAULT 'text',
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message`
--

LOCK TABLES `message` WRITE;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `milestones`
--

DROP TABLE IF EXISTS `milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `milestones` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('motor_skills','language','cognitive','social_emotional','self_care') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `min_age_months` int(11) NOT NULL DEFAULT 0,
  `max_age_months` int(11) NOT NULL DEFAULT 72,
  PRIMARY KEY (`milestone_id`),
  KEY `idx_milestone_category` (`category`),
  KEY `idx_milestone_age` (`min_age_months`,`max_age_months`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `milestones`
--

LOCK TABLES `milestones` WRITE;
/*!40000 ALTER TABLE `milestones` DISABLE KEYS */;
INSERT INTO `milestones` VALUES (1,'motor_skills','Holds head up','Can hold head steady without support',0,4),(2,'motor_skills','Rolls over','Rolls from tummy to back and back to tummy',3,6),(3,'motor_skills','Sits without support','Sits steadily without needing help',4,9),(4,'motor_skills','Crawls','Moves on hands and knees',6,12),(5,'motor_skills','Pulls to stand','Pulls up to standing position using furniture',8,12),(6,'motor_skills','Walks independently','Takes steps without holding onto anything',9,18),(7,'motor_skills','Runs','Can run with good coordination',18,30),(8,'motor_skills','Kicks a ball','Can kick a ball forward',18,30),(9,'motor_skills','Climbs stairs with alternating feet','Goes up stairs one foot per step',24,42),(10,'motor_skills','Hops on one foot','Can hop on one foot several times',36,60),(11,'motor_skills','Catches a bounced ball','Can catch a ball that bounces',36,60),(12,'motor_skills','Draws a person with 6 body parts','Draws recognizable human figure',48,72),(13,'language','Coos and babbles','Makes vowel sounds like \"oo\" and \"ah\"',0,6),(14,'language','Responds to name','Turns head when name is called',4,9),(15,'language','Says first word','Says a recognizable word like \"mama\" or \"dada\"',9,15),(16,'language','Says 10+ words','Uses at least 10 different words',12,24),(17,'language','Combines two words','Puts two words together like \"more milk\"',18,30),(18,'language','Uses short sentences','Speaks in 3-4 word sentences',24,36),(19,'language','Tells a simple story','Can narrate a short event or story',36,60),(20,'language','Asks \"why\" questions','Frequently asks why things happen',30,48),(21,'language','Uses past tense correctly','Says things like \"I walked\"',36,60),(22,'language','Knows most letters of alphabet','Can identify uppercase letters',48,72),(23,'cognitive','Follows moving objects with eyes','Tracks objects visually',0,4),(24,'cognitive','Finds hidden objects','Understands object permanence',6,12),(25,'cognitive','Stacks 2+ blocks','Can stack blocks on top of each other',12,24),(26,'cognitive','Sorts shapes and colors','Groups objects by shape or color',18,36),(27,'cognitive','Counts to 10','Can count objects up to 10',30,48),(28,'cognitive','Understands \"same\" and \"different\"','Can identify similarities and differences',36,48),(29,'cognitive','Knows basic colors','Names at least 4 colors correctly',30,48),(30,'cognitive','Understands time concepts','Grasps today/tomorrow/yesterday',36,60),(31,'cognitive','Writes own name','Can write first name',48,72),(32,'social_emotional','Social smile','Smiles in response to others',0,4),(33,'social_emotional','Shows stranger anxiety','Becomes upset around unfamiliar people',6,12),(34,'social_emotional','Plays alongside other children','Parallel play with peers',18,36),(35,'social_emotional','Shows empathy','Comforts a crying child or shows concern',18,36),(36,'social_emotional','Takes turns in play','Can share and take turns with others',30,48),(37,'social_emotional','Has a best friend','Forms a preferred friendship',36,60),(38,'social_emotional','Follows rules in simple games','Understands and follows game rules',36,60),(39,'social_emotional','Expresses complex emotions','Can describe feelings like frustration or excitement',48,72),(40,'self_care','Drinks from a cup','Holds and drinks from a cup with help',6,15),(41,'self_care','Uses a spoon','Feeds self with a spoon',12,24),(42,'self_care','Helps with dressing','Pulls off simple clothing items',12,24),(43,'self_care','Toilet trained (daytime)','Uses toilet independently during the day',24,42),(44,'self_care','Washes hands independently','Can wash and dry hands alone',24,42),(45,'self_care','Brushes teeth with help','Participates in tooth brushing',18,36),(46,'self_care','Dresses independently','Puts on clothes without help',36,60),(47,'self_care','Ties shoelaces','Can tie own shoes',48,72);
/*!40000 ALTER TABLE `milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moderation_log`
--

DROP TABLE IF EXISTS `moderation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moderation_log` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moderation_log`
--

LOCK TABLES `moderation_log` WRITE;
/*!40000 ALTER TABLE `moderation_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `moderation_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motor_milestones`
--

DROP TABLE IF EXISTS `motor_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `motor_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `milestone_name` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT 'gross_motor',
  `is_achieved` tinyint(1) DEFAULT 0,
  `achieved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `motor_milestones_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motor_milestones`
--

LOCK TABLES `motor_milestones` WRITE;
/*!40000 ALTER TABLE `motor_milestones` DISABLE KEYS */;
INSERT INTO `motor_milestones` VALUES (1,1,'Walk independently','Gross Motor',0,NULL,NULL,'2026-06-15 13:47:21'),(2,1,'Hold pencil','Fine Motor',0,NULL,NULL,'2026-06-15 13:47:21');
/*!40000 ALTER TABLE `motor_milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_history`
--

DROP TABLE IF EXISTS `newsletter_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `content` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nh_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_history`
--

LOCK TABLES `newsletter_history` WRITE;
/*!40000 ALTER TABLE `newsletter_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed` tinyint(1) DEFAULT 1,
  `preferences` text DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `type` enum('appointment_reminder','payment_success','growth_alert','milestone','system') DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `fk_clinic_notif` (`clinic_id`),
  CONSTRAINT `fk_clinic_notif` FOREIGN KEY (`clinic_id`) REFERENCES `clinic` (`clinic_id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent`
--

DROP TABLE IF EXISTS `parent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent` (
  `parent_id` int(11) NOT NULL,
  `number_of_children` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`parent_id`),
  CONSTRAINT `parent_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent`
--

LOCK TABLES `parent` WRITE;
/*!40000 ALTER TABLE `parent` DISABLE KEYS */;
INSERT INTO `parent` VALUES (76,2,'2026-06-15 13:47:20'),(77,4,'2026-06-15 13:47:20'),(78,4,'2026-06-15 13:47:20'),(79,4,'2026-06-15 13:47:20'),(80,3,'2026-06-15 13:47:20'),(81,5,'2026-06-15 13:47:20'),(82,3,'2026-06-15 13:47:20'),(83,3,'2026-06-15 13:47:20'),(84,5,'2026-06-15 13:47:20'),(85,5,'2026-06-15 13:47:20'),(86,3,'2026-06-15 13:47:20'),(87,4,'2026-06-15 13:47:20'),(88,4,'2026-06-15 13:47:20'),(89,3,'2026-06-15 13:47:20'),(90,4,'2026-06-15 13:47:20'),(91,3,'2026-06-15 13:47:20'),(92,2,'2026-06-15 13:47:20'),(93,3,'2026-06-15 13:47:20'),(94,5,'2026-06-15 13:47:20'),(95,3,'2026-06-15 13:47:20'),(96,4,'2026-06-15 13:47:20'),(97,3,'2026-06-15 13:47:20'),(98,5,'2026-06-15 13:47:20'),(99,4,'2026-06-15 13:47:20'),(100,2,'2026-06-15 13:47:21'),(101,2,'2026-06-15 13:47:21'),(102,5,'2026-06-15 13:47:21'),(103,3,'2026-06-15 13:47:21'),(104,4,'2026-06-15 13:47:21'),(105,5,'2026-06-15 13:47:21'),(106,3,'2026-06-15 13:47:21'),(107,5,'2026-06-15 13:47:21'),(108,3,'2026-06-15 13:47:21'),(109,5,'2026-06-15 13:47:21'),(110,4,'2026-06-15 13:47:21'),(111,3,'2026-06-15 13:47:21'),(112,4,'2026-06-15 13:47:21'),(113,5,'2026-06-15 13:47:21'),(114,2,'2026-06-15 13:47:21'),(115,5,'2026-06-15 13:47:21'),(116,4,'2026-06-15 13:47:21'),(117,3,'2026-06-15 13:47:21'),(118,3,'2026-06-15 13:47:21'),(119,3,'2026-06-15 13:47:21'),(120,4,'2026-06-15 13:47:21'),(121,4,'2026-06-15 13:47:21'),(122,3,'2026-06-15 13:47:21'),(123,3,'2026-06-15 13:47:21'),(124,4,'2026-06-15 13:47:21'),(125,3,'2026-06-15 13:47:21'),(126,3,'2026-06-15 13:47:21'),(127,2,'2026-06-15 13:47:21'),(128,4,'2026-06-15 13:47:21'),(129,4,'2026-06-15 13:47:21'),(130,2,'2026-06-15 13:47:21'),(131,3,'2026-06-15 13:47:21'),(132,4,'2026-06-15 13:47:21'),(133,3,'2026-06-15 13:47:21'),(134,4,'2026-06-15 13:47:21'),(135,4,'2026-06-15 13:47:21'),(136,5,'2026-06-15 13:47:21'),(137,3,'2026-06-15 13:47:21'),(138,5,'2026-06-15 13:47:21'),(139,4,'2026-06-15 13:47:21'),(140,3,'2026-06-15 13:47:21'),(141,2,'2026-06-15 13:47:21'),(142,4,'2026-06-15 13:47:21'),(143,3,'2026-06-15 13:47:21'),(144,4,'2026-06-15 13:47:21'),(145,5,'2026-06-15 13:47:21'),(146,5,'2026-06-15 13:47:21'),(147,4,'2026-06-15 13:47:21'),(148,3,'2026-06-15 13:47:21'),(149,4,'2026-06-15 13:47:21'),(150,4,'2026-06-15 13:47:21'),(151,3,'2026-06-15 13:47:21'),(152,3,'2026-06-15 13:47:21'),(153,5,'2026-06-15 13:47:21'),(154,4,'2026-06-15 13:47:21'),(155,4,'2026-06-15 13:47:21'),(156,3,'2026-06-15 13:47:21'),(157,2,'2026-06-15 13:47:21'),(158,5,'2026-06-15 13:47:21'),(159,3,'2026-06-15 13:47:21'),(160,5,'2026-06-15 13:47:21'),(161,4,'2026-06-15 13:47:21'),(162,3,'2026-06-15 13:47:21'),(163,4,'2026-06-15 13:47:21'),(164,5,'2026-06-15 13:47:21'),(165,4,'2026-06-15 13:47:21'),(166,2,'2026-06-15 13:47:21'),(167,2,'2026-06-15 13:47:21'),(168,3,'2026-06-15 13:47:21'),(169,5,'2026-06-15 13:47:21'),(170,2,'2026-06-15 13:47:21'),(171,4,'2026-06-15 13:47:21'),(172,2,'2026-06-15 13:47:21'),(173,5,'2026-06-15 13:47:21'),(174,4,'2026-06-15 13:47:21'),(175,3,'2026-06-15 13:47:21'),(176,4,'2026-06-15 13:47:21'),(177,4,'2026-06-15 13:47:21'),(178,3,'2026-06-15 13:47:21'),(179,4,'2026-06-15 13:47:21'),(180,4,'2026-06-15 13:47:21'),(181,3,'2026-06-15 13:47:21'),(182,4,'2026-06-15 13:47:21'),(183,3,'2026-06-15 13:47:21'),(184,2,'2026-06-15 13:47:21'),(185,5,'2026-06-15 13:47:21'),(186,4,'2026-06-15 13:47:21'),(187,3,'2026-06-15 13:47:21'),(188,3,'2026-06-15 13:47:21'),(189,3,'2026-06-15 13:47:21'),(190,4,'2026-06-15 13:47:21'),(191,3,'2026-06-15 13:47:21'),(192,3,'2026-06-15 13:47:21'),(193,3,'2026-06-15 13:47:21'),(194,5,'2026-06-15 13:47:21'),(195,4,'2026-06-15 13:47:21'),(196,4,'2026-06-15 13:47:21');
/*!40000 ALTER TABLE `parent` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER IF NOT EXISTS `trg_parent_points_wallet_init`
AFTER INSERT ON `parent`
FOR EACH ROW
BEGIN
    INSERT INTO `parent_points_wallet` (`parent_id`, `total_points`, `lifetime_earned`, `lifetime_redeemed`)
    VALUES (NEW.parent_id, 0, 0, 0)
    ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `parent_action_cooldowns`
--

DROP TABLE IF EXISTS `parent_action_cooldowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_action_cooldowns` (
  `cooldown_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `action_key` varchar(100) NOT NULL,
  `last_action_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `available_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`cooldown_id`),
  UNIQUE KEY `uk_parent_action` (`parent_id`,`action_key`),
  KEY `idx_available_at` (`available_at`),
  CONSTRAINT `fk_cooldown_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_action_cooldowns`
--

LOCK TABLES `parent_action_cooldowns` WRITE;
/*!40000 ALTER TABLE `parent_action_cooldowns` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_action_cooldowns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_onboarding`
--

DROP TABLE IF EXISTS `parent_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_onboarding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_name` varchar(100) DEFAULT NULL,
  `child_dob` date DEFAULT NULL,
  `child_gender` varchar(10) DEFAULT NULL,
  `primary_concerns` text DEFAULT NULL,
  `preferred_activities` text DEFAULT NULL,
  `development_goals` text DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `parent_onboarding_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_onboarding`
--

LOCK TABLES `parent_onboarding` WRITE;
/*!40000 ALTER TABLE `parent_onboarding` DISABLE KEYS */;
INSERT INTO `parent_onboarding` VALUES (1,9,'Sarah','2024-02-13','Female','[]','[]','[]','2026-04-04 23:00:14'),(2,10,'Emma','2020-01-01','Male','[]','[]','[]','2026-04-04 23:38:38'),(3,12,'Emma','2024-01-01','Male','[]','[]','[]','2026-04-05 00:30:37'),(5,49,'Emma','2024-01-01','Male','[]','[]','[]','2026-04-07 04:45:26'),(6,50,'Test Child','2023-01-01','Female','[]','[]','[]','2026-04-07 05:12:37'),(7,51,'Emma','0101-02-02','Male','[]','[]','[]','2026-04-07 05:51:56'),(8,53,'Emma','2020-01-01','Female','[]','[]','[]','2026-04-12 13:46:57'),(9,58,'paula','2025-02-16','Male','[]','[]','[]','2026-05-03 09:36:15'),(10,60,'mestif','2024-03-14','Male','[]','[]','[]','2026-05-23 01:05:02'),(11,61,'kidtest','2025-02-11','Male','[]','[]','[]','2026-05-23 12:43:06'),(12,62,'test','2024-02-13','Male','[]','[]','[]','2026-05-24 05:12:59'),(13,63,'hihi','2026-05-05','Male','[]','[]','[]','2026-06-11 10:31:44'),(14,65,'Sarah','2026-06-10','Male','[]','[]','[]','2026-06-11 16:26:10');
/*!40000 ALTER TABLE `parent_onboarding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_points_history`
--

DROP TABLE IF EXISTS `parent_points_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_points_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `points` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_points_history`
--

LOCK TABLES `parent_points_history` WRITE;
/*!40000 ALTER TABLE `parent_points_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_points_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_points_milestones`
--

DROP TABLE IF EXISTS `parent_points_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_points_milestones` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `milestone_type` enum('earned_total','redeemed_total','streak_days','action_count') NOT NULL,
  `milestone_value` int(11) NOT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `badge_icon` varchar(255) DEFAULT NULL,
  `achieved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`milestone_id`),
  UNIQUE KEY `uk_parent_milestone` (`parent_id`,`milestone_type`,`milestone_value`),
  CONSTRAINT `fk_milestone_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_points_milestones`
--

LOCK TABLES `parent_points_milestones` WRITE;
/*!40000 ALTER TABLE `parent_points_milestones` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_points_milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_points_tracking`
--

DROP TABLE IF EXISTS `parent_points_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_points_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `action_key` varchar(100) NOT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `earned_date` date NOT NULL,
  `week_start_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tracking_id`),
  UNIQUE KEY `uk_parent_action_date` (`parent_id`,`action_key`,`earned_date`),
  KEY `idx_week_tracking` (`parent_id`,`week_start_date`),
  CONSTRAINT `fk_tracking_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_points_tracking`
--

LOCK TABLES `parent_points_tracking` WRITE;
/*!40000 ALTER TABLE `parent_points_tracking` DISABLE KEYS */;
INSERT INTO `parent_points_tracking` VALUES (1,9,'record_speech',0,'2026-05-22','2026-05-18','2026-05-22 21:49:26'),(2,9,'record_speech',0,'2026-05-23','2026-05-18','2026-05-23 00:01:49'),(3,60,'log_growth',0,'2026-05-23','2026-05-18','2026-05-23 01:43:00'),(4,9,'log_growth',0,'2026-05-23','2026-05-18','2026-05-23 02:09:11'),(6,61,'log_growth',25,'2026-05-23','2026-05-18','2026-05-23 19:03:18'),(7,62,'log_growth',25,'2026-05-24','2026-05-18','2026-05-24 05:40:53'),(8,63,'log_growth',25,'2026-06-11','2026-06-08','2026-06-11 10:32:32'),(9,9,'log_growth',25,'2026-06-12','2026-06-08','2026-06-11 22:49:41');
/*!40000 ALTER TABLE `parent_points_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_points_wallet`
--

DROP TABLE IF EXISTS `parent_points_wallet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_points_wallet` (
  `wallet_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `lifetime_earned` int(11) DEFAULT 0,
  `lifetime_redeemed` int(11) DEFAULT 0,
  `last_earned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`wallet_id`),
  UNIQUE KEY `uk_parent_wallet` (`parent_id`),
  KEY `idx_total_points` (`total_points`),
  CONSTRAINT `fk_parent_wallet_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_points_wallet`
--

LOCK TABLES `parent_points_wallet` WRITE;
/*!40000 ALTER TABLE `parent_points_wallet` DISABLE KEYS */;
INSERT INTO `parent_points_wallet` VALUES (1,76,2500,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(3,77,911,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(5,78,761,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(7,79,781,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(9,80,197,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(11,81,1124,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(13,82,1004,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(15,83,1489,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(17,84,882,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(19,85,1362,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(21,86,50,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(23,87,778,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(25,88,415,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(27,89,863,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(29,90,271,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(31,91,315,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(33,92,825,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(35,93,1221,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(37,94,1231,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(39,95,432,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(41,96,1008,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(43,97,1298,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(45,98,1235,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:20'),(47,99,129,0,0,NULL,'2026-06-15 13:47:20','2026-06-15 13:47:21'),(49,100,522,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(51,101,529,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(53,102,594,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(55,103,1419,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(57,104,264,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(59,105,379,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(61,106,1335,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(63,107,1208,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(65,108,335,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(67,109,1250,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(69,110,732,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(71,111,797,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(73,112,85,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(75,113,1115,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(77,114,170,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(79,115,1153,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(81,116,854,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(83,117,335,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(85,118,471,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(87,119,141,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(89,120,300,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(91,121,466,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(93,122,908,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(95,123,618,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(97,124,105,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(99,125,885,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(101,126,964,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(103,127,690,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(105,128,877,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(107,129,1008,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(109,130,1281,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(111,131,933,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(113,132,1094,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(115,133,829,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(117,134,111,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(119,135,101,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(121,136,841,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(123,137,880,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(125,138,773,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(127,139,710,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(129,140,1125,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(131,141,763,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(133,142,178,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(135,143,1105,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(137,144,980,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(139,145,1483,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(141,146,276,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(143,147,1289,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(145,148,1076,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(147,149,428,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(149,150,1423,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(151,151,249,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(153,152,1183,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(155,153,1494,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(157,154,647,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(159,155,80,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(161,156,896,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(163,157,493,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(165,158,624,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(167,159,831,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(169,160,798,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(171,161,674,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(173,162,1096,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(175,163,1221,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(177,164,1150,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(179,165,826,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(181,166,1339,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(183,167,1284,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(185,168,720,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(187,169,335,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(189,170,56,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(191,171,1000,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(193,172,677,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(195,173,189,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(197,174,758,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(199,175,788,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(201,176,510,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(203,177,1458,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(205,178,411,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(207,179,1145,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(209,180,1446,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(211,181,953,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(213,182,568,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(215,183,970,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(217,184,828,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(219,185,652,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(221,186,862,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(223,187,213,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(225,188,920,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(227,189,95,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(229,190,1131,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(231,191,1453,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(233,192,379,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(235,193,221,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(237,194,486,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(239,195,389,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21'),(241,196,1239,0,0,NULL,'2026-06-15 13:47:21','2026-06-15 13:47:21');
/*!40000 ALTER TABLE `parent_points_wallet` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER IF NOT EXISTS `trg_parent_points_milestone`
AFTER UPDATE ON `parent_points_wallet`
FOR EACH ROW
BEGIN
    DECLARE milestone_reached INT DEFAULT 0;

    
    IF NEW.lifetime_earned >= 10000 AND OLD.lifetime_earned < 10000 THEN
        SET milestone_reached = 10000;
    ELSEIF NEW.lifetime_earned >= 5000 AND OLD.lifetime_earned < 5000 THEN
        SET milestone_reached = 5000;
    ELSEIF NEW.lifetime_earned >= 1000 AND OLD.lifetime_earned < 1000 THEN
        SET milestone_reached = 1000;
    ELSEIF NEW.lifetime_earned >= 500 AND OLD.lifetime_earned < 500 THEN
        SET milestone_reached = 500;
    ELSEIF NEW.lifetime_earned >= 100 AND OLD.lifetime_earned < 100 THEN
        SET milestone_reached = 100;
    END IF;

    IF milestone_reached > 0 THEN
        INSERT INTO `parent_points_milestones` (`parent_id`, `milestone_type`, `milestone_value`, `milestone_name`, `badge_icon`)
        VALUES (NEW.parent_id, 'earned_total', milestone_reached,
                CONCAT('Points Master - ', milestone_reached, ' Points'), '🏆')
        ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `parent_redeemed_offers`
--

DROP TABLE IF EXISTS `parent_redeemed_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_redeemed_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `child_id` int(11) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `points_spent` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'active',
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_redeemed_offers`
--

LOCK TABLES `parent_redeemed_offers` WRITE;
/*!40000 ALTER TABLE `parent_redeemed_offers` DISABLE KEYS */;
INSERT INTO `parent_redeemed_offers` VALUES (1,9,1,3,200,'active','2026-05-03 23:17:06');
/*!40000 ALTER TABLE `parent_redeemed_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_redemptions`
--

DROP TABLE IF EXISTS `parent_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_redemptions` (
  `redemption_id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `points_used` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('pending','active','used','expired','refunded') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`redemption_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`),
  KEY `fk_redemption_wallet` (`wallet_id`),
  KEY `fk_redemption_item` (`item_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_redemption_item` FOREIGN KEY (`item_id`) REFERENCES `redemption_catalog` (`item_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemption_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemption_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `parent_points_wallet` (`wallet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_redemptions`
--

LOCK TABLES `parent_redemptions` WRITE;
/*!40000 ALTER TABLE `parent_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_subscription`
--

DROP TABLE IF EXISTS `parent_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_subscription` (
  `parent_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`parent_id`,`subscription_id`,`child_name`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `parent_subscription_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  CONSTRAINT `parent_subscription_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_subscription`
--

LOCK TABLES `parent_subscription` WRITE;
/*!40000 ALTER TABLE `parent_subscription` DISABLE KEYS */;
INSERT INTO `parent_subscription` VALUES (76,3,'','2026-06-15 13:47:20','2025-01-01 00:00:00','active'),(88,3,'','2026-06-15 13:47:20','2025-01-01 00:00:00','active'),(89,3,'','2026-06-15 13:47:20','2025-01-01 00:00:00','active'),(93,3,'','2026-06-15 13:47:20','2025-01-01 00:00:00','active'),(96,3,'','2026-06-15 13:47:20','2025-01-01 00:00:00','active'),(105,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(117,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(121,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(128,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(132,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(133,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(139,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(141,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(149,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(157,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(161,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(170,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(171,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(172,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(182,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(184,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(186,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(189,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active'),(194,3,'','2026-06-15 13:47:21','2025-01-01 00:00:00','active');
/*!40000 ALTER TABLE `parent_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES (1,9,'$2y$10$hY/MNPEl19hBR83LcJCDQ.3bBUgeWimOSSmFPcF1Gox8E6tlQhRFa','2026-04-11 11:21:09',0,'2026-04-11 09:11:09'),(2,9,'$2y$10$B84lLlB3Ihjp85kryxtEseVCneI8WlDoF/IfWU9D6hC1fkqekpE8S','2026-04-11 11:21:13',0,'2026-04-11 09:11:13'),(3,9,'$2y$10$i3.7/4dPoigl.XY3NyTJsOq10Gd0Iwr6VqVZEEwwpdphOs/kO0JKK','2026-04-11 11:22:14',0,'2026-04-11 09:12:15'),(4,9,'$2y$10$U2onsFAo2rUbs99t..U0vepGxMfUrQBOPvdMmSxEguJLY3kNXhxDy','2026-04-11 11:37:25',0,'2026-04-11 09:27:25'),(5,9,'$2y$10$gF.jDobfVPFV/WMH9KQj9eAPwMsaA8.h7o57hV3gBXqoQcyxS.v4O','2026-04-11 11:37:56',0,'2026-04-11 09:27:56'),(6,9,'$2y$10$WzgayeQAmxV8M4z852Zis.vuwDKl6xShGGSgW.9nVqs6n3lBMeAP6','2026-04-11 11:51:02',0,'2026-04-11 09:41:02'),(7,57,'$2y$10$7FFJA5K6C2ExMEdfzIrBXO4.WWj90u3tppA8Fo3WQcyu3r3M5mgGG','2026-06-11 13:00:31',0,'2026-06-11 10:50:31'),(8,57,'$2y$10$rxPQWAVfX57mwY.yBOZ9butBWEgoplvhXF7x3nPHfg/hd8t55ucXG','2026-06-11 13:00:36',0,'2026-06-11 10:50:36'),(9,57,'$2y$10$HTwvt7.MzKaTXMrKJWHhguQJOwpJSRJe08D5fISESC..Tz0gKd0im','2026-06-11 13:00:40',0,'2026-06-11 10:50:40'),(10,57,'$2y$10$wi1UGnLIlikHbxo3WFPPKeCQlRWgVI0H0hpRTxLGVKl5QNa1g9heW','2026-06-11 13:00:44',0,'2026-06-11 10:50:44'),(11,57,'$2y$10$esih5I.78suFCGAGebzBvuBCO5dnjgso9M8aV/ZCfMXTpBAXRo2v6','2026-06-11 13:00:48',0,'2026-06-11 10:50:49');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `amount_pre_discount` decimal(10,2) DEFAULT NULL,
  `discount_rate` decimal(5,2) DEFAULT NULL,
  `amount_post_discount` decimal(10,2) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tokens_used` decimal(10,2) DEFAULT 0.00,
  `token_id` int(11) DEFAULT NULL,
  `points_redeemed` int(11) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `fk_payment_token` (`token_id`),
  CONSTRAINT `fk_payment_token` FOREIGN KEY (`token_id`) REFERENCES `appointment_tokens` (`token_id`) ON DELETE SET NULL,
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
INSERT INTO `payment` VALUES (1,NULL,NULL,250.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(2,NULL,NULL,250.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(3,NULL,NULL,250.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(4,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(5,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(6,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(7,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(8,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(9,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(10,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:21',0.00,NULL,0),(11,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(12,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(13,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(14,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(15,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(16,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(17,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(18,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(19,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(20,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(21,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(22,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(23,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(24,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(25,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(26,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(27,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(28,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(29,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(30,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(31,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(32,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(33,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(34,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(35,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(36,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(37,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(38,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(39,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(40,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(41,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(42,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(43,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(44,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(45,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(46,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(47,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(48,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(49,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(50,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(51,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(52,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(53,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(54,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(55,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(56,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(57,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(58,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(59,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(60,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(61,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(62,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(63,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(64,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(65,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(66,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(67,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(68,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(69,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(70,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(71,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(72,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(73,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(74,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(75,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(76,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(77,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(78,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(79,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:22',0.00,NULL,0),(80,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(81,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(82,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(83,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(84,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(85,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(86,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(87,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(88,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(89,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(90,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(91,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(92,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(93,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(94,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(95,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(96,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(97,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(98,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(99,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(100,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(101,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(102,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(103,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(104,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(105,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(106,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(107,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(108,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(109,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(110,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(111,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(112,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(113,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(114,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(115,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(116,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0),(117,NULL,NULL,200.00,NULL,NULL,'credit_card','completed','2026-06-15 13:47:23',0.00,NULL,0);
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_payment_before_insert` BEFORE INSERT ON `payment` FOR EACH ROW BEGIN
    SET NEW.amount_post_discount =
        NEW.amount_pre_discount -
        (NEW.amount_pre_discount * NEW.discount_rate);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES ('allow_clinic_registration','1','2026-04-12 16:07:41'),('auto_approve_clinics','0','2026-04-12 15:55:29'),('dark_mode_default','1','2026-04-12 16:07:35'),('data_sharing','1','2026-04-12 16:07:32'),('enable_free_trial','1','2026-04-12 16:07:46'),('language','en','2026-04-12 12:09:09'),('last_daily_digest','2026-06-15','2026-06-15 08:14:37'),('maintenance_mode','0','2026-04-30 10:23:45'),('test_key','1','2026-04-12 16:01:24'),('weekly_digest','1','2026-04-04 22:23:27');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_earning_rules`
--

DROP TABLE IF EXISTS `points_earning_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_earning_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_key` varchar(100) NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `points_value` int(11) NOT NULL DEFAULT 0,
  `daily_cap` int(11) DEFAULT NULL,
  `weekly_cap` int(11) DEFAULT NULL,
  `cooldown_minutes` int(11) DEFAULT 0,
  `requires_verification` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rule_id`),
  UNIQUE KEY `action_key` (`action_key`),
  KEY `idx_action_key` (`action_key`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_earning_rules`
--

LOCK TABLES `points_earning_rules` WRITE;
/*!40000 ALTER TABLE `points_earning_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `points_earning_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_expiration_rules`
--

DROP TABLE IF EXISTS `points_expiration_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_expiration_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(255) NOT NULL,
  `expiration_days` int(11) NOT NULL,
  `reminder_days_before` int(11) DEFAULT 7,
  `applies_to` enum('all','unredeemed','bonus_points') DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_expiration_rules`
--

LOCK TABLES `points_expiration_rules` WRITE;
/*!40000 ALTER TABLE `points_expiration_rules` DISABLE KEYS */;
INSERT INTO `points_expiration_rules` VALUES (1,'Standard Expiration',90,7,'unredeemed',1,'2026-05-22 21:42:45'),(2,'Bonus Points Expiration',30,3,'bonus_points',1,'2026-05-22 21:42:45'),(3,'Standard Expiration',90,7,'unredeemed',1,'2026-05-22 21:44:54'),(4,'Bonus Points Expiration',30,3,'bonus_points',1,'2026-05-22 21:44:54');
/*!40000 ALTER TABLE `points_expiration_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_refrence`
--

DROP TABLE IF EXISTS `points_refrence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_refrence` (
  `refrence_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action_name` varchar(100) DEFAULT NULL,
  `points_value` int(11) DEFAULT NULL,
  `adjust_sign` enum('+','-') DEFAULT NULL,
  PRIMARY KEY (`refrence_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `points_refrence_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_refrence`
--

LOCK TABLES `points_refrence` WRITE;
/*!40000 ALTER TABLE `points_refrence` DISABLE KEYS */;
INSERT INTO `points_refrence` VALUES (1,1,'read_article',5,'+'),(2,1,'complete_activity',35,'+'),(3,1,'streak_7day',50,'+'),(4,1,'streak_30day',250,'+'),(5,1,'streak_100day',1000,'+'),(6,1,'share_story',100,'+'),(7,1,'missed_checkin',5,'-'),(8,1,'free_consultation',500,'-'),(9,1,'record_speech',15,'+');
/*!40000 ALTER TABLE `points_refrence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_rules`
--

DROP TABLE IF EXISTS `points_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_rules` (
  `rule_key` varchar(50) NOT NULL,
  `points` int(11) NOT NULL,
  `daily_cap` int(11) DEFAULT NULL,
  `weekly_cap` int(11) DEFAULT NULL,
  `cooldown_minutes` int(11) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_rules`
--

LOCK TABLES `points_rules` WRITE;
/*!40000 ALTER TABLE `points_rules` DISABLE KEYS */;
INSERT INTO `points_rules` VALUES ('attend_appointment',50,50,100,1440,'Complete appointment',1),('complete_activity',35,70,200,30,'Complete recommended activity',1),('complete_child_profile',75,150,NULL,0,'Fill child profile',1),('complete_motor_activity',20,60,250,30,'Finish motor exercise',1),('complete_profile',100,100,NULL,0,'Fill parent profile',1),('daily_login',10,10,70,1440,'Log in once per day',1),('log_growth',25,25,100,43200,'Record growth (1 month cooldown)',1),('log_milestone',30,90,300,30,'Mark developmental milestone',1),('read_article',5,25,100,5,'Read parenting article',1),('record_speech',15,45,200,60,'Record child speaking',1),('refer_parent',200,NULL,NULL,0,'Refer another parent to join',1),('submit_feedback',20,40,80,60,'Provide feedback after an appointment',1),('weekly_goal',100,NULL,100,10080,'Complete weekly goals',1);
/*!40000 ALTER TABLE `points_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_transaction`
--

DROP TABLE IF EXISTS `points_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_transaction` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `refrence_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `points_change` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `refrence_id` (`refrence_id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `idx_parent` (`parent_id`),
  CONSTRAINT `points_transaction_ibfk_1` FOREIGN KEY (`refrence_id`) REFERENCES `points_refrence` (`refrence_id`),
  CONSTRAINT `points_transaction_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `points_wallet` (`wallet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_transaction`
--

LOCK TABLES `points_transaction` WRITE;
/*!40000 ALTER TABLE `points_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `points_transaction` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_points_transaction_before_insert` BEFORE INSERT ON `points_transaction` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_points_wallet_after_insert` AFTER INSERT ON `points_transaction` FOR EACH ROW BEGIN
    UPDATE points_wallet
    SET total_points = total_points + NEW.points_change
    WHERE wallet_id = NEW.wallet_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `points_verification_queue`
--

DROP TABLE IF EXISTS `points_verification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_verification_queue` (
  `verification_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `action_key` varchar(100) NOT NULL,
  `claimed_points` int(11) NOT NULL,
  `evidence_url` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`verification_id`),
  KEY `idx_status` (`status`),
  KEY `fk_verify_parent` (`parent_id`),
  CONSTRAINT `fk_verify_parent` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_verification_queue`
--

LOCK TABLES `points_verification_queue` WRITE;
/*!40000 ALTER TABLE `points_verification_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `points_verification_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_wallet`
--

DROP TABLE IF EXISTS `points_wallet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_wallet` (
  `wallet_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`wallet_id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `points_wallet_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_wallet`
--

LOCK TABLES `points_wallet` WRITE;
/*!40000 ALTER TABLE `points_wallet` DISABLE KEYS */;
INSERT INTO `points_wallet` VALUES (1,1,200,'2026-06-14 18:50:53'),(2,3,445,'2026-06-14 19:06:07'),(3,4,65,'2026-04-05 01:38:39'),(4,5,0,'2026-04-05 08:30:14'),(5,7,100,'2026-04-07 05:14:45'),(6,8,130,'2026-04-07 05:57:19'),(7,10,50,'2026-05-03 09:37:17'),(8,16,0,'2026-06-14 17:55:49');
/*!40000 ALTER TABLE `points_wallet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`prescription_id`),
  KEY `child_id` (`child_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limit_log`
--

DROP TABLE IF EXISTS `rate_limit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(200) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rate_ip_endpoint` (`ip_address`,`endpoint`),
  KEY `idx_rate_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limit_log`
--

LOCK TABLES `rate_limit_log` WRITE;
/*!40000 ALTER TABLE `rate_limit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redemption_catalog`
--

DROP TABLE IF EXISTS `redemption_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redemption_catalog` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type` enum('appointment','service','badge','custom') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_cost` int(11) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `requires_specialist` tinyint(1) DEFAULT 0,
  `specialist_id` int(11) DEFAULT NULL,
  `max_redemptions_per_user` int(11) DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `badge_color` varchar(50) DEFAULT 'blue',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_catalog_specialist` (`specialist_id`),
  CONSTRAINT `fk_catalog_specialist` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redemption_catalog`
--

LOCK TABLES `redemption_catalog` WRITE;
/*!40000 ALTER TABLE `redemption_catalog` DISABLE KEYS */;
INSERT INTO `redemption_catalog` VALUES (1,'appointment','Appointment Token (25% off)','Redeem for 25% off any specialist appointment',500,50.00,25.00,1,0,NULL,NULL,NULL,'­ƒÄ½','green','2026-05-22 21:42:45','2026-05-22 21:42:45'),(2,'appointment','Appointment Token (50% off)','Redeem for 50% off any specialist appointment',900,50.00,50.00,1,0,NULL,NULL,NULL,'­ƒÄƒ´©Å','blue','2026-05-22 21:42:45','2026-05-22 21:42:45'),(3,'appointment','Free Appointment','Complete appointment covered by points',1500,50.00,100.00,1,0,NULL,NULL,NULL,'­ƒÅÑ','purple','2026-05-22 21:42:45','2026-05-22 21:42:45'),(4,'service','Extended Session (30min)','Extra 30 minutes added to appointment',300,25.00,0.00,1,1,NULL,NULL,NULL,'ÔÅ▒´©Å','orange','2026-05-22 21:42:45','2026-05-22 21:42:45'),(5,'service','Priority Booking','Skip waiting list for urgent appointments',400,15.00,0.00,1,0,NULL,NULL,NULL,'Ô¡É','yellow','2026-05-22 21:42:45','2026-05-22 21:42:45'),(6,'service','Home Visit Discount','$20 off home visit surcharge',250,20.00,0.00,1,0,NULL,NULL,NULL,'­ƒÅá','red','2026-05-22 21:42:45','2026-05-22 21:42:45'),(7,'badge','Premium Badge','Exclusive profile badge display',1000,NULL,NULL,1,0,NULL,NULL,NULL,'­ƒÅå','gold','2026-05-22 21:42:45','2026-05-22 21:42:45'),(8,'custom','Gift Card $10','$10 credit to your account',1200,10.00,0.00,1,0,NULL,NULL,NULL,'­ƒÆ│','green','2026-05-22 21:42:45','2026-05-22 21:42:45'),(9,'custom','Gift Card $25','$25 credit to your account',2800,25.00,0.00,1,0,NULL,NULL,NULL,'­ƒÆ│','blue','2026-05-22 21:42:45','2026-05-22 21:42:45'),(10,'appointment','Appointment Token (25% off)','Redeem for 25% off any specialist appointment',500,50.00,25.00,1,0,NULL,NULL,NULL,'­ƒÄ½','green','2026-05-22 21:44:54','2026-05-22 21:44:54'),(11,'appointment','Appointment Token (50% off)','Redeem for 50% off any specialist appointment',900,50.00,50.00,1,0,NULL,NULL,NULL,'­ƒÄƒ´©Å','blue','2026-05-22 21:44:54','2026-05-22 21:44:54'),(12,'appointment','Free Appointment','Complete appointment covered by points',1500,50.00,100.00,1,0,NULL,NULL,NULL,'­ƒÅÑ','purple','2026-05-22 21:44:54','2026-05-22 21:44:54'),(13,'service','Extended Session (30min)','Extra 30 minutes added to appointment',300,25.00,0.00,1,1,NULL,NULL,NULL,'ÔÅ▒´©Å','orange','2026-05-22 21:44:54','2026-05-22 21:44:54'),(14,'service','Priority Booking','Skip waiting list for urgent appointments',400,15.00,0.00,1,0,NULL,NULL,NULL,'Ô¡É','yellow','2026-05-22 21:44:54','2026-05-22 21:44:54'),(15,'service','Home Visit Discount','$20 off home visit surcharge',250,20.00,0.00,1,0,NULL,NULL,NULL,'­ƒÅá','red','2026-05-22 21:44:54','2026-05-22 21:44:54'),(16,'badge','Premium Badge','Exclusive profile badge display',1000,NULL,NULL,1,0,NULL,NULL,NULL,'­ƒÅå','gold','2026-05-22 21:44:54','2026-05-22 21:44:54'),(17,'custom','Gift Card $10','$10 credit to your account',1200,10.00,0.00,1,0,NULL,NULL,NULL,'­ƒÆ│','green','2026-05-22 21:44:54','2026-05-22 21:44:54'),(18,'custom','Gift Card $25','$25 credit to your account',2800,25.00,0.00,1,0,NULL,NULL,NULL,'­ƒÆ│','blue','2026-05-22 21:44:54','2026-05-22 21:44:54');
/*!40000 ALTER TABLE `redemption_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_offers`
--

DROP TABLE IF EXISTS `reward_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward_offers` (
  `offer_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT '??',
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_plan` enum('standard','premium','all') DEFAULT 'all',
  PRIMARY KEY (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_offers`
--

LOCK TABLES `reward_offers` WRITE;
/*!40000 ALTER TABLE `reward_offers` DISABLE KEYS */;
INSERT INTO `reward_offers` VALUES (4,NULL,'10% Off Clinic Appointment','Get a 10% discount on your next clinic appointment booking.',2000,'🎟️',1,'2026-06-11 22:46:03','premium'),(5,NULL,'25% Off Clinic Appointment','Get a 25% discount on your next clinic appointment booking.',4500,'🎫',1,'2026-06-11 22:46:03','premium'),(6,NULL,'50% Off Clinic Appointment','Get a massive 50% discount on your next clinic appointment booking.',8000,'🔥',1,'2026-06-11 22:46:03','premium'),(7,NULL,'Free Clinic Appointment','Redeem points for a completely free 1-on-1 specialist consultation.',15000,'👩‍⚕️',1,'2026-06-11 22:46:03','premium'),(8,NULL,'10% Off Premium Plan Renewal','Get a 10% discount on your next Premium subscription renewal.',3000,'💎',1,'2026-06-11 22:46:03','premium'),(12,NULL,'1 Free Month of Premium','Upgrade your account to Premium for 30 days and unlock all exclusive features.',1000,'👑',1,'2026-06-15 12:58:16','standard'),(13,NULL,'Full Developmental Report','Get a comprehensive AI-generated developmental report for your child.',500,'📄',1,'2026-06-15 12:58:16','standard'),(14,NULL,'3 Free Motor & Speech Trials','Unlock 3 additional free trials for motor and speech analysis tools.',300,'🗣️',1,'2026-06-15 12:58:16','standard'),(15,NULL,'Priority Support Ticket','Skip the queue and get your support ticket answered within 2 hours.',150,'⚡',1,'2026-06-15 12:58:16','standard');
/*!40000 ALTER TABLE `reward_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shared_reports`
--

DROP TABLE IF EXISTS `shared_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shared_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(500) DEFAULT NULL,
  `report_type` varchar(50) DEFAULT 'full-report',
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `is_shared` tinyint(1) DEFAULT 1,
  `doctor_reply` text DEFAULT NULL,
  `doctor_reply_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_sr_child` (`child_id`),
  KEY `idx_sr_parent` (`parent_id`),
  KEY `idx_sr_doctor` (`doctor_id`),
  KEY `idx_sr_appointment` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shared_reports`
--

LOCK TABLES `shared_reports` WRITE;
/*!40000 ALTER TABLE `shared_reports` DISABLE KEYS */;
INSERT INTO `shared_reports` VALUES (1,'api_export_pdf.php?type=full-report&child_id=1','full-report',1,9,47,14,1,NULL,NULL,'2026-06-12 16:01:58'),(2,NULL,'full-report',1,9,71,NULL,1,'Report written on 2026-06-13. See \'My Reports\' tab.','2026-06-13 00:00:00','2026-06-13 17:25:29'),(3,'api_export_pdf.php?type=full-report&child_id=1','full-report',1,9,71,15,1,'Report written on 2026-06-13. See \'My Reports\' tab.','2026-06-13 00:00:00','2026-06-13 17:48:57');
/*!40000 ALTER TABLE `shared_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specialist`
--

DROP TABLE IF EXISTS `specialist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialist` (
  `specialist_id` int(11) NOT NULL AUTO_INCREMENT,
  `clinic_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `certificate_of_experience` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `consultation_fee` decimal(10,2) DEFAULT 50.00,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `certification_text` text DEFAULT NULL,
  `certification_pdf` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `consultation_types` set('online','onsite') DEFAULT 'online,onsite',
  `certifications` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`specialist_id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `fk_specialist_user` FOREIGN KEY (`specialist_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `specialist_clinic_fk` FOREIGN KEY (`clinic_id`) REFERENCES `clinic` (`clinic_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialist`
--

LOCK TABLES `specialist` WRITE;
/*!40000 ALTER TABLE `specialist` DISABLE KEYS */;
INSERT INTO `specialist` VALUES (15,2,'Salsabel','Ahmed','Speech Therapist',NULL,5,'2026-06-15 13:47:20',250.00,'Experienced speech therapist specializing in early childhood language development. Dedicated to providing personalized therapy plans.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(16,5,'Fatma','Hassan','Special Education Teacher',NULL,2,'2026-06-15 13:47:20',370.00,'Dedicated Special Education Teacher with 2 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(17,7,'Youssef','Fathy','Occupational Therapist',NULL,5,'2026-06-15 13:47:20',283.00,'Dedicated Occupational Therapist with 5 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(18,11,'Hamza','Ali','Speech Therapist',NULL,7,'2026-06-15 13:47:20',442.00,'Dedicated Speech Therapist with 7 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(19,10,'Maha','Ibrahim','Child Psychologist',NULL,17,'2026-06-15 13:47:20',471.00,'Dedicated Child Psychologist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(20,14,'Tarek','Shawky','Child Psychologist',NULL,15,'2026-06-15 13:47:20',168.00,'Dedicated Child Psychologist with 15 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(21,11,'Aya','Said','Physical Therapist',NULL,18,'2026-06-15 13:47:20',152.00,'Dedicated Physical Therapist with 18 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(22,6,'Reem','Tariq','Behavioral Therapist',NULL,7,'2026-06-15 13:47:20',190.00,'Dedicated Behavioral Therapist with 7 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(23,8,'Reem','Zaki','Behavioral Therapist',NULL,6,'2026-06-15 13:47:20',481.00,'Dedicated Behavioral Therapist with 6 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(24,8,'Marwan','Adel','Special Education Teacher',NULL,5,'2026-06-15 13:47:20',173.00,'Dedicated Special Education Teacher with 5 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(25,2,'Aya','Fathy','Special Education Teacher',NULL,9,'2026-06-15 13:47:20',226.00,'Dedicated Special Education Teacher with 9 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(26,11,'Nada','Hassan','Special Education Teacher',NULL,12,'2026-06-15 13:47:20',426.00,'Dedicated Special Education Teacher with 12 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(27,11,'Hana','Radwan','Behavioral Therapist',NULL,6,'2026-06-15 13:47:20',157.00,'Dedicated Behavioral Therapist with 6 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(28,6,'Tarek','Kamal','Special Education Teacher',NULL,19,'2026-06-15 13:47:20',245.00,'Dedicated Special Education Teacher with 19 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(29,5,'Adam','Radwan','Occupational Therapist',NULL,5,'2026-06-15 13:47:20',344.00,'Dedicated Occupational Therapist with 5 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(30,11,'Hassan','Saleh','Special Education Teacher',NULL,2,'2026-06-15 13:47:20',405.00,'Dedicated Special Education Teacher with 2 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(31,14,'Ahmed','Saleh','Occupational Therapist',NULL,17,'2026-06-15 13:47:20',245.00,'Dedicated Occupational Therapist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(32,14,'Ibrahim','Ibrahim','Occupational Therapist',NULL,15,'2026-06-15 13:47:20',425.00,'Dedicated Occupational Therapist with 15 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(33,14,'Nour','Kamel','Child Psychologist',NULL,1,'2026-06-15 13:47:20',300.00,'Dedicated Child Psychologist with 1 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(34,11,'Laila','Hassan','Speech Therapist',NULL,17,'2026-06-15 13:47:20',412.00,'Dedicated Speech Therapist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(35,5,'Laila','Roshdy','Behavioral Therapist',NULL,13,'2026-06-15 13:47:20',479.00,'Dedicated Behavioral Therapist with 13 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(36,5,'Sara','Tariq','Physical Therapist',NULL,9,'2026-06-15 13:47:20',238.00,'Dedicated Physical Therapist with 9 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(37,9,'Aya','Mahmoud','Child Psychologist',NULL,17,'2026-06-15 13:47:20',333.00,'Dedicated Child Psychologist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(38,5,'Adam','Ibrahim','Special Education Teacher',NULL,9,'2026-06-15 13:47:20',238.00,'Dedicated Special Education Teacher with 9 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(39,5,'Laila','Sami','Speech Therapist',NULL,12,'2026-06-15 13:47:20',344.00,'Dedicated Speech Therapist with 12 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(40,10,'Reem','Tariq','Child Psychologist',NULL,10,'2026-06-15 13:47:20',370.00,'Dedicated Child Psychologist with 10 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(41,7,'Ibrahim','Kamal','Behavioral Therapist',NULL,17,'2026-06-15 13:47:20',228.00,'Dedicated Behavioral Therapist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(42,10,'Jana','Hassan','Occupational Therapist',NULL,4,'2026-06-15 13:47:20',222.00,'Dedicated Occupational Therapist with 4 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(43,4,'Reem','Ibrahim','Speech Therapist',NULL,15,'2026-06-15 13:47:20',388.00,'Dedicated Speech Therapist with 15 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(44,4,'Nada','Mansour','Special Education Teacher',NULL,14,'2026-06-15 13:47:20',485.00,'Dedicated Special Education Teacher with 14 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(45,7,'Khaled','Fathy','Physical Therapist',NULL,17,'2026-06-15 13:47:20',305.00,'Dedicated Physical Therapist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(46,9,'Hassan','Osman','Speech Therapist',NULL,15,'2026-06-15 13:47:20',171.00,'Dedicated Speech Therapist with 15 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(47,14,'Rana','Shawky','Special Education Teacher',NULL,7,'2026-06-15 13:47:20',279.00,'Dedicated Special Education Teacher with 7 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(48,7,'Salma','Osman','Child Psychologist',NULL,17,'2026-06-15 13:47:20',428.00,'Dedicated Child Psychologist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(49,12,'Rana','Sami','Child Psychologist',NULL,3,'2026-06-15 13:47:20',188.00,'Dedicated Child Psychologist with 3 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(50,14,'Yasmin','Osman','Speech Therapist',NULL,16,'2026-06-15 13:47:20',373.00,'Dedicated Speech Therapist with 16 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(51,3,'Dina','Roshdy','Occupational Therapist',NULL,20,'2026-06-15 13:47:20',310.00,'Dedicated Occupational Therapist with 20 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(52,13,'Marwan','Kamal','Physical Therapist',NULL,5,'2026-06-15 13:47:20',201.00,'Dedicated Physical Therapist with 5 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(53,14,'Amr','Ibrahim','Speech Therapist',NULL,20,'2026-06-15 13:47:20',355.00,'Dedicated Speech Therapist with 20 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(54,12,'Habiba','Kamel','Speech Therapist',NULL,19,'2026-06-15 13:47:20',307.00,'Dedicated Speech Therapist with 19 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(55,13,'Ziad','Fathy','Occupational Therapist',NULL,3,'2026-06-15 13:47:20',325.00,'Dedicated Occupational Therapist with 3 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(56,13,'Laila','Zaki','Physical Therapist',NULL,3,'2026-06-15 13:47:20',167.00,'Dedicated Physical Therapist with 3 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(57,7,'Hana','Kamel','Speech Therapist',NULL,4,'2026-06-15 13:47:20',429.00,'Dedicated Speech Therapist with 4 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(58,9,'Hamza','Adel','Special Education Teacher',NULL,1,'2026-06-15 13:47:20',355.00,'Dedicated Special Education Teacher with 1 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(59,7,'Ahmed','Kamel','Child Psychologist',NULL,15,'2026-06-15 13:47:20',304.00,'Dedicated Child Psychologist with 15 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(60,7,'Hassan','Osman','Child Psychologist',NULL,17,'2026-06-15 13:47:20',186.00,'Dedicated Child Psychologist with 17 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(61,4,'Dina','Kamel','Behavioral Therapist',NULL,13,'2026-06-15 13:47:20',300.00,'Dedicated Behavioral Therapist with 13 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(62,12,'Mohamed','Saleh','Speech Therapist',NULL,11,'2026-06-15 13:47:20',453.00,'Dedicated Speech Therapist with 11 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(63,7,'Nour','Mansour','Child Psychologist',NULL,14,'2026-06-15 13:47:20',310.00,'Dedicated Child Psychologist with 14 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(64,10,'Ziad','Radwan','Physical Therapist',NULL,14,'2026-06-15 13:47:20',158.00,'Dedicated Physical Therapist with 14 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(65,11,'Amr','Saleh','Child Psychologist',NULL,12,'2026-06-15 13:47:20',500.00,'Dedicated Child Psychologist with 12 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(66,4,'Hamza','Ibrahim','Special Education Teacher',NULL,8,'2026-06-15 13:47:20',326.00,'Dedicated Special Education Teacher with 8 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(67,13,'Yasmin','Kamel','Special Education Teacher',NULL,8,'2026-06-15 13:47:20',464.00,'Dedicated Special Education Teacher with 8 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(68,5,'Adam','Said','Special Education Teacher',NULL,4,'2026-06-15 13:47:20',380.00,'Dedicated Special Education Teacher with 4 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(69,10,'Marwan','Ali','Behavioral Therapist',NULL,7,'2026-06-15 13:47:20',355.00,'Dedicated Behavioral Therapist with 7 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(70,5,'Hana','Shawky','Special Education Teacher',NULL,11,'2026-06-15 13:47:20',328.00,'Dedicated Special Education Teacher with 11 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(71,11,'Jana','Roshdy','Speech Therapist',NULL,13,'2026-06-15 13:47:20',168.00,'Dedicated Speech Therapist with 13 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(72,13,'Noha','Saleh','Child Psychologist',NULL,19,'2026-06-15 13:47:20',443.00,'Dedicated Child Psychologist with 19 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(73,8,'Farida','Ali','Child Psychologist',NULL,14,'2026-06-15 13:47:20',294.00,'Dedicated Child Psychologist with 14 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(74,2,'Mohamed','Shawky','Speech Therapist',NULL,13,'2026-06-15 13:47:20',273.00,'Dedicated Speech Therapist with 13 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL),(75,10,'Omar','Kamel','Special Education Teacher',NULL,4,'2026-06-15 13:47:20',182.00,'Dedicated Special Education Teacher with 4 years of experience.',NULL,NULL,NULL,NULL,'online,onsite',NULL,NULL);
/*!40000 ALTER TABLE `specialist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specialist_availability`
--

DROP TABLE IF EXISTS `specialist_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialist_availability` (
  `availability_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday ... 6=Saturday',
  `start_time` time NOT NULL DEFAULT '09:00:00',
  `end_time` time NOT NULL DEFAULT '17:00:00',
  `slot_duration_minutes` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`availability_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `specialist_avail_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialist_availability`
--

LOCK TABLES `specialist_availability` WRITE;
/*!40000 ALTER TABLE `specialist_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `specialist_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specialist_reviews`
--

DROP TABLE IF EXISTS `specialist_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialist_reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `specialist_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `parent_id` (`parent_id`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `specialist_reviews_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  CONSTRAINT `specialist_reviews_ibfk_2` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`specialist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialist_reviews`
--

LOCK TABLES `specialist_reviews` WRITE;
/*!40000 ALTER TABLE `specialist_reviews` DISABLE KEYS */;
INSERT INTO `specialist_reviews` VALUES (1,76,15,NULL,5,'Dr. Salsabel is amazing with Omar! His speech has improved significantly.','2023-11-25 08:00:00'),(2,78,54,NULL,4,'Good session.','2026-06-15 13:47:21'),(3,82,58,NULL,4,'Good session.','2026-06-15 13:47:21'),(4,86,61,NULL,4,'Good session.','2026-06-15 13:47:22'),(5,87,69,NULL,5,'Good session.','2026-06-15 13:47:22'),(6,90,37,NULL,5,'Good session.','2026-06-15 13:47:22'),(7,96,37,NULL,4,'Good session.','2026-06-15 13:47:22'),(8,96,56,NULL,3,'Good session.','2026-06-15 13:47:22'),(9,98,39,NULL,4,'Good session.','2026-06-15 13:47:22'),(10,99,32,NULL,5,'Good session.','2026-06-15 13:47:22'),(11,100,47,NULL,4,'Good session.','2026-06-15 13:47:22'),(12,102,62,NULL,4,'Good session.','2026-06-15 13:47:22'),(13,104,56,NULL,4,'Good session.','2026-06-15 13:47:22'),(14,108,75,NULL,3,'Good session.','2026-06-15 13:47:22'),(15,109,39,NULL,5,'Good session.','2026-06-15 13:47:22'),(16,109,51,NULL,4,'Good session.','2026-06-15 13:47:22'),(17,112,32,NULL,3,'Good session.','2026-06-15 13:47:22'),(18,113,73,NULL,4,'Good session.','2026-06-15 13:47:22'),(19,115,58,NULL,3,'Good session.','2026-06-15 13:47:22'),(20,117,24,NULL,5,'Good session.','2026-06-15 13:47:22'),(21,119,23,NULL,3,'Good session.','2026-06-15 13:47:22'),(22,122,24,NULL,3,'Good session.','2026-06-15 13:47:22'),(23,122,68,NULL,4,'Good session.','2026-06-15 13:47:22'),(24,126,70,NULL,5,'Good session.','2026-06-15 13:47:22'),(25,127,28,NULL,5,'Good session.','2026-06-15 13:47:22'),(26,132,58,NULL,3,'Good session.','2026-06-15 13:47:22'),(27,133,35,NULL,5,'Good session.','2026-06-15 13:47:22'),(28,135,15,NULL,3,'Good session.','2026-06-15 13:47:22'),(29,135,26,NULL,5,'Good session.','2026-06-15 13:47:22'),(30,136,67,NULL,3,'Good session.','2026-06-15 13:47:22'),(31,137,58,NULL,3,'Good session.','2026-06-15 13:47:22'),(32,137,42,NULL,5,'Good session.','2026-06-15 13:47:22'),(33,141,21,NULL,5,'Good session.','2026-06-15 13:47:22'),(34,150,42,NULL,3,'Good session.','2026-06-15 13:47:22'),(35,151,30,NULL,4,'Good session.','2026-06-15 13:47:22'),(36,151,37,NULL,5,'Good session.','2026-06-15 13:47:22'),(37,153,33,NULL,4,'Good session.','2026-06-15 13:47:22'),(38,154,20,NULL,5,'Good session.','2026-06-15 13:47:23'),(39,156,68,NULL,3,'Good session.','2026-06-15 13:47:23'),(40,159,54,NULL,4,'Good session.','2026-06-15 13:47:23'),(41,165,50,NULL,5,'Good session.','2026-06-15 13:47:23'),(42,167,31,NULL,4,'Good session.','2026-06-15 13:47:23'),(43,168,39,NULL,4,'Good session.','2026-06-15 13:47:23'),(44,169,66,NULL,4,'Good session.','2026-06-15 13:47:23'),(45,174,53,NULL,4,'Good session.','2026-06-15 13:47:23'),(46,176,37,NULL,5,'Good session.','2026-06-15 13:47:23'),(47,177,64,NULL,4,'Good session.','2026-06-15 13:47:23'),(48,178,61,NULL,4,'Good session.','2026-06-15 13:47:23'),(49,180,17,NULL,3,'Good session.','2026-06-15 13:47:23'),(50,184,49,NULL,5,'Good session.','2026-06-15 13:47:23'),(51,186,66,NULL,3,'Good session.','2026-06-15 13:47:23'),(52,187,27,NULL,4,'Good session.','2026-06-15 13:47:23'),(53,189,65,NULL,5,'Good session.','2026-06-15 13:47:23'),(54,193,54,NULL,3,'Good session.','2026-06-15 13:47:23'),(55,193,66,NULL,5,'Good session.','2026-06-15 13:47:23'),(56,194,37,NULL,4,'Good session.','2026-06-15 13:47:23'),(57,196,39,NULL,4,'Good session.','2026-06-15 13:47:23');
/*!40000 ALTER TABLE `specialist_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `speech_analysis`
--

DROP TABLE IF EXISTS `speech_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `speech_analysis` (
  `sample_id` int(11) NOT NULL,
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transcript` text DEFAULT NULL,
  `vocabulary_score` decimal(5,2) DEFAULT NULL,
  `clarify_score` decimal(5,2) DEFAULT NULL,
  `match_score` decimal(5,2) DEFAULT NULL,
  `sentence_count` int(11) DEFAULT NULL,
  `avg_sentence_length` decimal(5,2) DEFAULT NULL,
  `sentence_complexity_score` decimal(5,2) DEFAULT NULL,
  `avg_word_length` decimal(5,2) DEFAULT NULL,
  `avg_syllables_per_word` decimal(5,2) DEFAULT NULL,
  `polysyllabic_word_count` int(11) DEFAULT NULL,
  `flesch_reading_ease` decimal(6,2) DEFAULT NULL,
  `flesch_kincaid_grade` decimal(5,2) DEFAULT NULL,
  `overall_development_score` decimal(5,2) DEFAULT NULL,
  `developmental_feedback` text DEFAULT NULL,
  PRIMARY KEY (`sample_id`,`analyzed_at`),
  CONSTRAINT `speech_analysis_ibfk_1` FOREIGN KEY (`sample_id`) REFERENCES `voice_sample` (`sample_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `speech_analysis`
--

LOCK TABLES `speech_analysis` WRITE;
/*!40000 ALTER TABLE `speech_analysis` DISABLE KEYS */;
INSERT INTO `speech_analysis` VALUES (1,'2026-06-15 13:47:21','Mama give me apple',88.00,0.88,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,88.00,'Needs minor grammatical correction.'),(2,'2026-06-15 13:47:21','He want go outside play',71.00,0.71,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,71.00,'Needs minor grammatical correction.'),(3,'2026-06-15 13:47:21','I no like sleep time',72.00,0.72,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,72.00,'Needs minor grammatical correction.'),(4,'2026-06-15 13:47:21','I no like sleep time',70.00,0.70,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70.00,'Needs minor grammatical correction.'),(5,'2026-06-15 13:47:21','Test recording',89.00,0.89,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,89.00,'Automated feedback.'),(6,'2026-06-15 13:47:22','Test recording',74.00,0.74,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,74.00,'Automated feedback.'),(7,'2026-06-15 13:47:22','Test recording',78.00,0.78,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,78.00,'Automated feedback.'),(8,'2026-06-15 13:47:22','Test recording',56.00,0.56,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,56.00,'Automated feedback.'),(9,'2026-06-15 13:47:22','Test recording',61.00,0.61,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,61.00,'Automated feedback.'),(10,'2026-06-15 13:47:22','Test recording',60.00,0.60,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,60.00,'Automated feedback.'),(11,'2026-06-15 13:47:22','Test recording',84.00,0.84,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,84.00,'Automated feedback.'),(12,'2026-06-15 13:47:22','Test recording',85.00,0.85,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,85.00,'Automated feedback.'),(13,'2026-06-15 13:47:22','Test recording',50.00,0.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,50.00,'Automated feedback.'),(14,'2026-06-15 13:47:22','Test recording',79.00,0.79,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,79.00,'Automated feedback.'),(15,'2026-06-15 13:47:22','Test recording',56.00,0.56,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,56.00,'Automated feedback.'),(16,'2026-06-15 13:47:22','Test recording',79.00,0.79,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,79.00,'Automated feedback.'),(17,'2026-06-15 13:47:22','Test recording',72.00,0.72,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,72.00,'Automated feedback.'),(18,'2026-06-15 13:47:22','Test recording',58.00,0.58,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,58.00,'Automated feedback.'),(19,'2026-06-15 13:47:22','Test recording',86.00,0.86,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,86.00,'Automated feedback.'),(20,'2026-06-15 13:47:22','Test recording',74.00,0.74,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,74.00,'Automated feedback.'),(21,'2026-06-15 13:47:22','Test recording',67.00,0.67,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,67.00,'Automated feedback.'),(22,'2026-06-15 13:47:22','Test recording',56.00,0.56,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,56.00,'Automated feedback.'),(23,'2026-06-15 13:47:22','Test recording',55.00,0.55,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,55.00,'Automated feedback.'),(24,'2026-06-15 13:47:22','Test recording',50.00,0.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,50.00,'Automated feedback.'),(25,'2026-06-15 13:47:22','Test recording',85.00,0.85,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,85.00,'Automated feedback.'),(26,'2026-06-15 13:47:22','Test recording',88.00,0.88,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,88.00,'Automated feedback.'),(27,'2026-06-15 13:47:22','Test recording',56.00,0.56,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,56.00,'Automated feedback.'),(28,'2026-06-15 13:47:22','Test recording',59.00,0.59,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,59.00,'Automated feedback.'),(29,'2026-06-15 13:47:22','Test recording',62.00,0.62,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,62.00,'Automated feedback.'),(30,'2026-06-15 13:47:22','Test recording',71.00,0.71,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,71.00,'Automated feedback.'),(31,'2026-06-15 13:47:22','Test recording',57.00,0.57,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,57.00,'Automated feedback.'),(32,'2026-06-15 13:47:22','Test recording',82.00,0.82,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,82.00,'Automated feedback.'),(33,'2026-06-15 13:47:22','Test recording',75.00,0.75,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,75.00,'Automated feedback.'),(34,'2026-06-15 13:47:22','Test recording',88.00,0.88,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,88.00,'Automated feedback.'),(35,'2026-06-15 13:47:22','Test recording',58.00,0.58,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,58.00,'Automated feedback.'),(36,'2026-06-15 13:47:22','Test recording',73.00,0.73,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,73.00,'Automated feedback.'),(37,'2026-06-15 13:47:22','Test recording',52.00,0.52,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,52.00,'Automated feedback.'),(38,'2026-06-15 13:47:22','Test recording',65.00,0.65,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,65.00,'Automated feedback.'),(39,'2026-06-15 13:47:22','Test recording',90.00,0.90,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,90.00,'Automated feedback.'),(40,'2026-06-15 13:47:22','Test recording',61.00,0.61,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,61.00,'Automated feedback.'),(41,'2026-06-15 13:47:23','Test recording',69.00,0.69,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,69.00,'Automated feedback.'),(42,'2026-06-15 13:47:23','Test recording',50.00,0.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,50.00,'Automated feedback.'),(43,'2026-06-15 13:47:23','Test recording',87.00,0.87,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,87.00,'Automated feedback.'),(44,'2026-06-15 13:47:23','Test recording',74.00,0.74,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,74.00,'Automated feedback.'),(45,'2026-06-15 13:47:23','Test recording',85.00,0.85,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,85.00,'Automated feedback.'),(46,'2026-06-15 13:47:23','Test recording',80.00,0.80,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,80.00,'Automated feedback.'),(47,'2026-06-15 13:47:23','Test recording',67.00,0.67,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,67.00,'Automated feedback.'),(48,'2026-06-15 13:47:23','Test recording',75.00,0.75,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,75.00,'Automated feedback.'),(49,'2026-06-15 13:47:23','Test recording',84.00,0.84,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,84.00,'Automated feedback.'),(50,'2026-06-15 13:47:23','Test recording',51.00,0.51,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,51.00,'Automated feedback.'),(51,'2026-06-15 13:47:23','Test recording',50.00,0.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,50.00,'Automated feedback.'),(52,'2026-06-15 13:47:23','Test recording',89.00,0.89,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,89.00,'Automated feedback.'),(53,'2026-06-15 13:47:23','Test recording',77.00,0.77,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,77.00,'Automated feedback.'),(54,'2026-06-15 13:47:23','Test recording',84.00,0.84,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,84.00,'Automated feedback.'),(55,'2026-06-15 13:47:23','Test recording',60.00,0.60,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,60.00,'Automated feedback.'),(56,'2026-06-15 13:47:23','Test recording',79.00,0.79,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,79.00,'Automated feedback.');
/*!40000 ALTER TABLE `speech_analysis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streaks`
--

DROP TABLE IF EXISTS `streaks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `streaks` (
  `streak_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `streak_type` varchar(50) NOT NULL COMMENT 'growth_tracking, milestone_logging, daily_login',
  `current_count` int(11) DEFAULT 0,
  `longest_count` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`streak_id`),
  UNIQUE KEY `child_streak_type` (`child_id`,`streak_type`),
  KEY `idx_parent_streak` (`parent_id`,`streak_type`),
  CONSTRAINT `streaks_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `streaks`
--

LOCK TABLES `streaks` WRITE;
/*!40000 ALTER TABLE `streaks` DISABLE KEYS */;
/*!40000 ALTER TABLE `streaks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription`
--

DROP TABLE IF EXISTS `subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription` (
  `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(100) DEFAULT NULL,
  `plan_period` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`subscription_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription`
--

LOCK TABLES `subscription` WRITE;
/*!40000 ALTER TABLE `subscription` DISABLE KEYS */;
INSERT INTO `subscription` VALUES (1,'Free Trial','monthly',0.00,'','active'),(3,'Premium','monthly',250.00,'','active');
/*!40000 ALTER TABLE `subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_feature`
--

DROP TABLE IF EXISTS `subscription_feature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_feature` (
  `feature_id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `feature_text` varchar(255) NOT NULL,
  PRIMARY KEY (`feature_id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `sub_feature_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`subscription_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_feature`
--

LOCK TABLES `subscription_feature` WRITE;
/*!40000 ALTER TABLE `subscription_feature` DISABLE KEYS */;
INSERT INTO `subscription_feature` VALUES (15,1,'Basic growth tracking'),(16,1,'free trial of motor skills & Speech analysis'),(17,1,'1 child profile'),(18,1,'Monthly reports'),(19,3,'Everything in Standard'),(20,3,'Unlimited child profiles'),(21,3,'Priority support'),(22,3,'unlimited Refreshes'),(23,3,'unlimited Speech Analysis & Motor Skills');
/*!40000 ALTER TABLE `subscription_feature` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
INSERT INTO `support_tickets` VALUES (4,43,NULL,NULL,'Wrong report generated','medium','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(5,83,NULL,NULL,'Cannot login','high','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(6,104,NULL,NULL,'Cannot login','high','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(7,170,NULL,NULL,'Update my email','high','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(8,187,NULL,NULL,'Payment failed','medium','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(9,67,NULL,NULL,'Payment failed','high','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(10,117,NULL,NULL,'Payment failed','low','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(11,128,NULL,NULL,'How to book an appointment?','low','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(12,153,NULL,NULL,'Wrong report generated','medium','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(13,99,NULL,NULL,'Wrong report generated','low','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(14,43,NULL,NULL,'Cannot login','medium','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(15,71,NULL,NULL,'How to book an appointment?','medium','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(16,91,NULL,NULL,'Update my email','high','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(17,195,NULL,NULL,'How to book an appointment?','high','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(18,68,NULL,NULL,'Wrong report generated','low','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(19,30,NULL,NULL,'Update my email','medium','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(20,59,NULL,NULL,'Wrong report generated','low','open',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(21,97,NULL,NULL,'Update my email','low','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(22,189,NULL,NULL,'Wrong report generated','low','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(23,146,NULL,NULL,'Update my email','low','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(24,145,NULL,NULL,'Cannot login','high','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(25,148,NULL,NULL,'Payment failed','low','resolved',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(26,73,NULL,NULL,'How to book an appointment?','low','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(27,42,NULL,NULL,'Cannot login','low','in_progress',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23'),(28,59,NULL,NULL,'Update my email','high','closed',NULL,'2026-06-15 13:47:23','2026-06-15 13:47:23');
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_config`
--

LOCK TABLES `system_config` WRITE;
/*!40000 ALTER TABLE `system_config` DISABLE KEYS */;
INSERT INTO `system_config` VALUES (1,'maintenance_mode','0','Enable or disable maintenance mode (1=on, 0=off)','2026-04-30 01:07:58');
/*!40000 ALTER TABLE `system_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
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
  `is_resolved` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'unresolved',
  PRIMARY KEY (`id`),
  KEY `idx_sl_level` (`level`),
  KEY `idx_sl_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,'info','System started successfully','/admin/overview.php','GET',NULL,NULL,NULL,45,'2026-04-04 22:23:27',0,'unresolved'),(2,'warning','Slow query detected: 2.3s','/api_who_compare.php','GET',NULL,NULL,NULL,2300,'2026-04-04 22:23:27',1,'unresolved'),(3,'error','Failed to send notification email','/api_email_verify.php','POST',NULL,NULL,NULL,1500,'2026-04-04 22:23:27',0,'unresolved');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` enum('user','admin') DEFAULT 'user',
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Internal admin notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tm_ticket` (`ticket_id`),
  CONSTRAINT `tm_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `token_blacklist`
--

DROP TABLE IF EXISTS `token_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token_blacklist` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `token_blacklist`
--

LOCK TABLES `token_blacklist` WRITE;
/*!40000 ALTER TABLE `token_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `token_blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
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
  `system_alerts` tinyint(1) DEFAULT 1,
  `weekly_reports` tinyint(1) DEFAULT 1,
  `points_notifications` tinyint(1) DEFAULT 1,
  `points_milestone_alerts` tinyint(1) DEFAULT 1,
  `points_expiration_reminders` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (9,'light','en',1,1,1,1,1,1,'2026-05-22 23:26:55',1,1,1,1,1),(10,'light','en',1,1,1,1,1,1,'2026-04-04 23:38:39',1,1,1,1,1),(12,'light','en',1,1,1,1,1,1,'2026-04-05 00:30:37',1,1,1,1,1),(13,'light','en',1,1,1,1,1,1,'2026-04-05 02:16:20',1,1,1,1,1),(48,'light','en',1,1,1,1,1,1,'2026-04-05 10:53:26',1,1,1,1,1),(49,'light','en',1,1,1,1,1,1,'2026-04-07 04:45:26',1,1,1,1,1),(50,'light','en',1,1,1,1,1,1,'2026-04-07 05:12:37',1,1,1,1,1),(51,'light','en',1,1,1,1,1,1,'2026-04-07 05:51:56',1,1,1,1,1),(53,'light','en',1,1,1,1,1,1,'2026-04-12 13:46:57',1,1,1,1,1),(58,'light','en',1,1,1,1,1,1,'2026-05-03 09:36:15',1,1,1,1,1),(60,'light','en',1,1,1,1,1,1,'2026-05-23 01:05:02',1,1,1,1,1),(61,'light','en',1,1,1,1,1,1,'2026-05-23 12:47:24',1,1,1,1,1),(62,'light','en',1,1,1,1,1,1,'2026-05-24 05:12:59',1,1,1,1,1),(63,'light','en',1,1,1,1,1,1,'2026-06-11 10:31:44',1,1,1,1,1),(65,'light','en',1,1,1,1,1,1,'2026-06-11 16:26:11',1,1,1,1,1);
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System','Admin','admin@brightsteps.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','admin','active','2026-06-15 13:47:20',NULL,0),(2,'Mallak','Clinic','mallak@gmail.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(3,'Amr','Shawky','clinic0@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(4,'Ziad','Kamel','clinic1@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(5,'Youssef','Kamal','clinic2@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(6,'Hussein','Said','clinic3@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(7,'Mostafa','Ibrahim','clinic4@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(8,'Tarek','Fathy','clinic5@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(9,'Hussein','Osman','clinic6@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(10,'Yassin','Roshdy','clinic7@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(11,'Youssef','Ali','clinic8@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(12,'Ahmed','Sami','clinic9@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(13,'Adam','Hassan','clinic10@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(14,'Hamza','Fathy','clinic11@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','clinic','active','2026-06-15 13:47:20',NULL,0),(15,'Salsabel','Ahmed','salsabel@gmail.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(16,'Fatma','Hassan','specialist0@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(17,'Youssef','Fathy','specialist1@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(18,'Hamza','Ali','specialist2@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(19,'Maha','Ibrahim','specialist3@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(20,'Tarek','Shawky','specialist4@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(21,'Aya','Said','specialist5@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(22,'Reem','Tariq','specialist6@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(23,'Reem','Zaki','specialist7@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(24,'Marwan','Adel','specialist8@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(25,'Aya','Fathy','specialist9@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(26,'Nada','Hassan','specialist10@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(27,'Hana','Radwan','specialist11@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(28,'Tarek','Kamal','specialist12@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(29,'Adam','Radwan','specialist13@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(30,'Hassan','Saleh','specialist14@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(31,'Ahmed','Saleh','specialist15@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(32,'Ibrahim','Ibrahim','specialist16@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(33,'Nour','Kamel','specialist17@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(34,'Laila','Hassan','specialist18@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(35,'Laila','Roshdy','specialist19@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(36,'Sara','Tariq','specialist20@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(37,'Aya','Mahmoud','specialist21@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(38,'Adam','Ibrahim','specialist22@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(39,'Laila','Sami','specialist23@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(40,'Reem','Tariq','specialist24@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(41,'Ibrahim','Kamal','specialist25@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(42,'Jana','Hassan','specialist26@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(43,'Reem','Ibrahim','specialist27@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(44,'Nada','Mansour','specialist28@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(45,'Khaled','Fathy','specialist29@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(46,'Hassan','Osman','specialist30@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(47,'Rana','Shawky','specialist31@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(48,'Salma','Osman','specialist32@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(49,'Rana','Sami','specialist33@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(50,'Yasmin','Osman','specialist34@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(51,'Dina','Roshdy','specialist35@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(52,'Marwan','Kamal','specialist36@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(53,'Amr','Ibrahim','specialist37@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(54,'Habiba','Kamel','specialist38@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(55,'Ziad','Fathy','specialist39@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(56,'Laila','Zaki','specialist40@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(57,'Hana','Kamel','specialist41@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(58,'Hamza','Adel','specialist42@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(59,'Ahmed','Kamel','specialist43@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(60,'Hassan','Osman','specialist44@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(61,'Dina','Kamel','specialist45@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(62,'Mohamed','Saleh','specialist46@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(63,'Nour','Mansour','specialist47@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(64,'Ziad','Radwan','specialist48@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(65,'Amr','Saleh','specialist49@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(66,'Hamza','Ibrahim','specialist50@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(67,'Yasmin','Kamel','specialist51@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','suspended','2026-06-15 13:47:20',NULL,0),(68,'Adam','Said','specialist52@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(69,'Marwan','Ali','specialist53@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(70,'Hana','Shawky','specialist54@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(71,'Jana','Roshdy','specialist55@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(72,'Noha','Saleh','specialist56@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(73,'Farida','Ali','specialist57@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(74,'Mohamed','Shawky','specialist58@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','active','2026-06-15 13:47:20',NULL,0),(75,'Omar','Kamel','specialist59@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','specialist','inactive','2026-06-15 13:47:20',NULL,0),(76,'Moaz','Ali','moaz@gmail.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(77,'Khaled','Kamel','parent0@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(78,'Aya','Mahmoud','parent1@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(79,'Aya','Roshdy','parent2@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(80,'Farida','Saleh','parent3@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(81,'Noha','Tariq','parent4@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(82,'Farida','Mahmoud','parent5@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(83,'Youssef','Kamal','parent6@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(84,'Khaled','Shawky','parent7@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(85,'Noha','Mansour','parent8@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(86,'Seif','Osman','parent9@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(87,'Maha','Roshdy','parent10@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(88,'Mona','Kamel','parent11@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(89,'Hussein','Hassan','parent12@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(90,'Reem','Said','parent13@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(91,'Dina','Ibrahim','parent14@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(92,'Hassan','Hassan','parent15@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(93,'Ziad','Fathy','parent16@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(94,'Mariam','Kamal','parent17@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(95,'Mona','Kamal','parent18@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(96,'Mariam','Adel','parent19@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(97,'Mona','Osman','parent20@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(98,'Youssef','Tariq','parent21@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:20',NULL,0),(99,'Mona','Shawky','parent22@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:20',NULL,0),(100,'Farida','Shawky','parent23@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(101,'Mohamed','Tariq','parent24@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(102,'Habiba','Mahmoud','parent25@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(103,'Ali','Fawzy','parent26@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(104,'Reem','Said','parent27@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(105,'Ahmed','Roshdy','parent28@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(106,'Sara','Adel','parent29@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(107,'Hamza','Adel','parent30@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(108,'Yassin','Kamel','parent31@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(109,'Mahmoud','Said','parent32@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(110,'Yassin','Gaber','parent33@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(111,'Maha','Mansour','parent34@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(112,'Adam','Kamel','parent35@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(113,'Mona','Radwan','parent36@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(114,'Ahmed','Roshdy','parent37@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(115,'Dina','Kamel','parent38@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(116,'Omar','Fawzy','parent39@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(117,'Ziad','Mahmoud','parent40@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(118,'Heba','Kamal','parent41@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(119,'Yasmin','Hassan','parent42@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(120,'Yassin','Tariq','parent43@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(121,'Seif','Ibrahim','parent44@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(122,'Jana','Hassan','parent45@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(123,'Tarek','Tariq','parent46@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(124,'Rana','Shawky','parent47@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(125,'Youssef','Kamal','parent48@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(126,'Fatma','Hassan','parent49@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(127,'Ibrahim','Sami','parent50@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(128,'Ahmed','Tariq','parent51@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(129,'Khaled','Roshdy','parent52@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(130,'Reem','Ibrahim','parent53@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(131,'Omar','Shawky','parent54@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(132,'Heba','Kamel','parent55@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(133,'Laila','Osman','parent56@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(134,'Adam','Saleh','parent57@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(135,'Heba','Sami','parent58@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(136,'Reem','Hassan','parent59@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(137,'Ibrahim','Saleh','parent60@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(138,'Ahmed','Fathy','parent61@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(139,'Yasmin','Tariq','parent62@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(140,'Heba','Ali','parent63@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(141,'Mariam','Kamel','parent64@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(142,'Mostafa','Ibrahim','parent65@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(143,'Marwan','Ali','parent66@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(144,'Khaled','Sami','parent67@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(145,'Adam','Roshdy','parent68@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(146,'Mariam','Saleh','parent69@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(147,'Omar','Roshdy','parent70@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(148,'Farida','Shawky','parent71@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(149,'Mona','Tariq','parent72@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(150,'Mariam','Radwan','parent73@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(151,'Reem','Radwan','parent74@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(152,'Fatma','Adel','parent75@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(153,'Kareem','Fathy','parent76@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(154,'Habiba','Hassan','parent77@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(155,'Hussein','Osman','parent78@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(156,'Rana','Osman','parent79@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(157,'Heba','Sami','parent80@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(158,'Ibrahim','Saleh','parent81@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(159,'Ziad','Radwan','parent82@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(160,'Noha','Osman','parent83@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(161,'Habiba','Roshdy','parent84@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(162,'Maha','Hassan','parent85@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(163,'Mahmoud','Roshdy','parent86@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(164,'Khaled','Fawzy','parent87@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(165,'Khaled','Saleh','parent88@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(166,'Yassin','Hassan','parent89@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(167,'Heba','Sami','parent90@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(168,'Hana','Sami','parent91@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(169,'Amr','Ibrahim','parent92@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(170,'Nour','Fathy','parent93@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(171,'Reem','Osman','parent94@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(172,'Laila','Ibrahim','parent95@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(173,'Aya','Radwan','parent96@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(174,'Dina','Fathy','parent97@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(175,'Tarek','Gaber','parent98@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(176,'Dina','Kamel','parent99@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(177,'Nour','Radwan','parent100@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(178,'Salma','Said','parent101@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(179,'Kareem','Sami','parent102@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(180,'Ziad','Radwan','parent103@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(181,'Jana','Shawky','parent104@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(182,'Adam','Mahmoud','parent105@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(183,'Khaled','Gaber','parent106@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(184,'Aya','Zaki','parent107@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(185,'Salma','Shawky','parent108@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(186,'Reem','Roshdy','parent109@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(187,'Adam','Saleh','parent110@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(188,'Mostafa','Kamel','parent111@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(189,'Hassan','Shawky','parent112@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(190,'Sara','Saleh','parent113@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(191,'Maha','Gaber','parent114@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','inactive','2026-06-15 13:47:21',NULL,0),(192,'Hamza','Adel','parent115@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(193,'Hamza','Gaber','parent116@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(194,'Youssef','Radwan','parent117@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(195,'Laila','Radwan','parent118@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0),(196,'Habiba','Radwan','parent119@example.com','$2y$10$63wOccpZV2T/SO89.weX0eungduqJOJGpezQG35xpyMsqYUbGY8P2','parent','active','2026-06-15 13:47:21',NULL,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_parent_points_summary`
--

DROP TABLE IF EXISTS `v_parent_points_summary`;
/*!50001 DROP VIEW IF EXISTS `v_parent_points_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_parent_points_summary` AS SELECT
 1 AS `parent_id`,
  1 AS `email`,
  1 AS `wallet_id`,
  1 AS `total_points`,
  1 AS `lifetime_earned`,
  1 AS `lifetime_redeemed`,
  1 AS `last_earned_at`,
  1 AS `active_redemptions`,
  1 AS `available_token_value` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_weekly_points_leaderboard`
--

DROP TABLE IF EXISTS `v_weekly_points_leaderboard`;
/*!50001 DROP VIEW IF EXISTS `v_weekly_points_leaderboard`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_weekly_points_leaderboard` AS SELECT
 1 AS `parent_id`,
  1 AS `email`,
  1 AS `weekly_points`,
  1 AS `weekly_rank` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `voice_sample`
--

DROP TABLE IF EXISTS `voice_sample`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voice_sample` (
  `sample_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode` varchar(20) DEFAULT 'free_talk',
  `target_text` text DEFAULT NULL,
  PRIMARY KEY (`sample_id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `voice_sample_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `child` (`child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voice_sample`
--

LOCK TABLES `voice_sample` WRITE;
/*!40000 ALTER TABLE `voice_sample` DISABLE KEYS */;
INSERT INTO `voice_sample` VALUES (1,1,'Within expected range','dummy.mp3','2026-06-15 13:47:21','free_talk',NULL),(2,1,'Within expected range','dummy.mp3','2026-06-15 13:47:21','free_talk',NULL),(3,1,'Within expected range','dummy.mp3','2026-06-15 13:47:21','free_talk',NULL),(4,1,'Within expected range','dummy.mp3','2026-06-15 13:47:21','free_talk',NULL),(5,12,'Within expected range','dummy.mp3','2026-06-15 13:47:21','free_talk',NULL),(6,14,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(7,16,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(8,21,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(9,26,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(10,38,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(11,40,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(12,42,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(13,43,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(14,46,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(15,48,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(16,51,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(17,52,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(18,53,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(19,54,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(20,55,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(21,59,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(22,65,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(23,67,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(24,68,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(25,72,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(26,74,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(27,76,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(28,78,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(29,80,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(30,82,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(31,96,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(32,102,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(33,111,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(34,114,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(35,116,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(36,117,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(37,120,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(38,124,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(39,127,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(40,128,'Within expected range','dummy.mp3','2026-06-15 13:47:22','free_talk',NULL),(41,131,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(42,140,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(43,142,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(44,144,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(45,145,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(46,148,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(47,151,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(48,154,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(49,155,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(50,161,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(51,164,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(52,172,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(53,182,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(54,192,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(55,193,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL),(56,194,'Within expected range','dummy.mp3','2026-06-15 13:47:23','free_talk',NULL);
/*!40000 ALTER TABLE `voice_sample` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `v_parent_points_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_parent_points_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_parent_points_summary` AS select `p`.`parent_id` AS `parent_id`,`u`.`email` AS `email`,`ppw`.`wallet_id` AS `wallet_id`,`ppw`.`total_points` AS `total_points`,`ppw`.`lifetime_earned` AS `lifetime_earned`,`ppw`.`lifetime_redeemed` AS `lifetime_redeemed`,`ppw`.`last_earned_at` AS `last_earned_at`,(select count(0) from `parent_redemptions` `pr` where `pr`.`parent_id` = `p`.`parent_id` and `pr`.`status` = 'active') AS `active_redemptions`,(select sum(`at`.`discount_amount`) from `appointment_tokens` `at` where `at`.`parent_id` = `p`.`parent_id` and `at`.`status` = 'available') AS `available_token_value` from ((`parent` `p` join `users` `u` on(`p`.`parent_id` = `u`.`user_id`)) left join `parent_points_wallet` `ppw` on(`p`.`parent_id` = `ppw`.`parent_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_weekly_points_leaderboard`
--

/*!50001 DROP VIEW IF EXISTS `v_weekly_points_leaderboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_weekly_points_leaderboard` AS select `p`.`parent_id` AS `parent_id`,`u`.`email` AS `email`,coalesce(sum(`ppt`.`points_earned`),0) AS `weekly_points`,rank() over ( order by coalesce(sum(`ppt`.`points_earned`),0) desc) AS `weekly_rank` from ((`parent` `p` join `users` `u` on(`p`.`parent_id` = `u`.`user_id`)) left join `parent_points_tracking` `ppt` on(`p`.`parent_id` = `ppt`.`parent_id` and `ppt`.`week_start_date` = curdate() - interval weekday(curdate()) day)) group by `p`.`parent_id`,`u`.`email` order by coalesce(sum(`ppt`.`points_earned`),0) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-15 16:47:27
