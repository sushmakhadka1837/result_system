-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: result_system
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_events`
--

DROP TABLE IF EXISTS `academic_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `event_type` enum('exam','event','holiday') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_events`
--

LOCK TABLES `academic_events` WRITE;
/*!40000 ALTER TABLE `academic_events` DISABLE KEYS */;
INSERT INTO `academic_events` VALUES (1,'Unit Test','exam','2025-12-07','2025-12-13','2025-12-13 16:43:15'),(2,'Christmas','holiday','2025-12-25','2025-12-25','2025-12-13 16:43:15'),(3,'Sports Meet','event','2025-12-26','2025-12-31','2025-12-13 16:43:15'),(4,'Expo','event','2026-01-01','2026-01-02','2025-12-13 16:43:15'),(5,'Assessment Exam','exam','2026-02-08','2026-02-15','2025-12-13 16:43:15');
/*!40000 ALTER TABLE `academic_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_code` varchar(255) DEFAULT NULL,
  `reset_code` varchar(255) DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (7,'aahanakhadka6@gmail.com','$2y$10$AxQ.TytHaLgM40iE33o5E.49OVX42CCnlBoP/ieC7X4crA5OaZr0i','37c54e32aa6953066e84a43fc974341b','admin','2025-12-11 14:30:39','2025-09-05 23:45:33',1,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('present','absent') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batches`
--

DROP TABLE IF EXISTS `batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `batches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `batch_year` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batches`
--

LOCK TABLES `batches` WRITE;
/*!40000 ALTER TABLE `batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL,
  `duration_years` int NOT NULL,
  `total_semesters` int NOT NULL,
  `hod_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_name` (`department_name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (2,'Computer',4,8,5),(3,'BEIT',4,8,NULL),(4,'Architecture',5,10,NULL),(5,'civil',4,8,0);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `elective_options`
--

DROP TABLE IF EXISTS `elective_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elective_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int DEFAULT NULL,
  `option_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `elective_options`
--

LOCK TABLES `elective_options` WRITE;
/*!40000 ALTER TABLE `elective_options` DISABLE KEYS */;
INSERT INTO `elective_options` VALUES (1,55,'ELE3');
/*!40000 ALTER TABLE `elective_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `message` text,
  `response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marks`
--

DROP TABLE IF EXISTS `marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `semester` int NOT NULL,
  `marks_type` varchar(50) NOT NULL DEFAULT 'General',
  `notes` text,
  `marks_obtained` decimal(5,2) NOT NULL,
  `full_marks` decimal(5,2) NOT NULL,
  `attendance_present` int NOT NULL DEFAULT '0',
  `attendance_total` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_published` tinyint(1) DEFAULT '0',
  `is_notified` tinyint(1) NOT NULL DEFAULT '0',
  `teacher_id` int NOT NULL,
  `ut_marks` decimal(6,2) DEFAULT '0.00',
  `assignment` decimal(6,2) DEFAULT '0.00',
  `practical` decimal(6,2) DEFAULT '0.00',
  `other` decimal(6,2) DEFAULT '0.00',
  `attendance_marks` decimal(6,2) DEFAULT '0.00',
  `internal_total` decimal(6,2) DEFAULT '0.00',
  `external_marks` decimal(6,2) DEFAULT '0.00',
  `final_total` decimal(6,2) DEFAULT '0.00',
  `attendance_days` int DEFAULT '0',
  `total_attendance` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_marks_entry` (`student_id`,`subject_id`,`semester`,`marks_type`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marks`
--

LOCK TABLES `marks` WRITE;
/*!40000 ALTER TABLE `marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sender_type` enum('student','teacher') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,12,7,'hello sir','2025-10-21 20:10:41','student','2025-10-21 20:17:03','2025-10-21 13:27:16',0),(2,12,7,'hiee','2025-10-21 20:17:52','student','2025-10-21 20:17:52','2025-10-21 13:27:16',0),(3,12,4,'hello sir','2025-10-21 20:21:35','student','2025-10-21 20:21:35','2025-10-21 13:27:16',1),(4,12,4,'hello','2025-10-21 20:27:53','student','2025-10-21 20:27:53','2025-10-21 13:27:53',1),(5,12,4,'hello sir ','2025-10-21 20:44:23','student','2025-10-21 20:44:23','2025-10-21 13:44:23',1),(6,12,4,'hello sir ','2025-10-21 20:44:33','student','2025-10-21 20:44:33','2025-10-21 13:44:33',1),(7,12,4,'hello','2025-10-21 21:01:37','student','2025-10-21 21:01:37','2025-10-21 14:01:37',1),(8,12,4,'hello sir how are you','2025-10-21 21:04:01','student','2025-10-21 21:04:01','2025-10-21 14:04:01',1),(9,12,4,'sir k gardai hunu hunxa','2025-10-21 21:09:39','student','2025-10-21 21:09:39','2025-10-21 14:09:39',1),(10,12,4,'sir k gardai hunu hunxa','2025-10-21 21:09:44','student','2025-10-21 21:09:44','2025-10-21 14:09:44',1),(11,12,4,'hi sir k gardai hunu hunxa','2025-10-21 21:14:28','student','2025-10-21 21:14:28','2025-10-21 14:14:28',1),(12,5,7,'hello\r\n','2025-10-24 17:38:34','teacher','2025-10-24 17:38:34','2025-10-24 10:38:34',0),(13,5,12,'hello milan\r\nhow are you doing','2025-10-24 18:04:44','teacher','2025-10-24 18:04:44','2025-10-24 11:04:44',0),(14,5,12,'hello milan\r\nhow are you doing','2025-10-24 18:04:48','teacher','2025-10-24 18:04:48','2025-10-24 11:04:48',0),(15,5,12,'hope you re doing well\r\n','2025-10-24 18:05:28','teacher','2025-10-24 18:05:28','2025-10-24 11:05:28',0),(16,12,5,'hello sir','2025-10-24 18:08:29','student','2025-10-24 18:08:29','2025-10-24 11:08:29',0),(17,14,6,'hello sir I\'m sandesh sapkota from computer department, 8th sem. sir mero internal marks bigreko xa can you please check it once','2025-10-25 02:12:01','student','2025-10-25 02:12:01','2025-10-24 19:12:01',0),(18,6,14,'ok dear student kati chahiyo marks 80 ki 90?\r\n','2025-10-25 02:13:18','teacher','2025-10-25 02:13:18','2025-10-24 19:13:18',1),(19,12,8,'hello sir how are you','2025-10-30 19:15:30','student','2025-10-30 19:15:30','2025-10-30 12:15:30',0),(20,14,6,'hello sir\r\n','2025-11-18 16:26:17','student','2025-11-18 16:26:17','2025-11-18 22:11:17',0),(21,12,4,'hello sir\r\n','2025-11-19 16:00:34','student','2025-11-19 16:00:34','2025-11-19 21:45:34',0),(22,13,4,'hello sir ','2025-11-21 06:45:57','student','2025-11-21 06:45:57','2025-11-21 12:30:57',0);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `semester_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `note_type` enum('notes','syllabus','past_questions') DEFAULT 'notes',
  `file_path` varchar(255) DEFAULT NULL,
  `uploader_id` int NOT NULL,
  `uploader_role` enum('teacher','student') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `note_year` int NOT NULL DEFAULT '2022',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (5,2,7,22,'Ai notes chapter1',NULL,'notes','uploads/notes/1765605556_AI [UNIT I] - AI INTRO-20251213T055641Z-3-001.zip',14,'student','2025-12-13 05:59:16',2022),(4,2,7,21,'chapter1-4',NULL,'notes','uploads/notes/1765604306_Eng. Eco. Ch1 to 4.pdf',14,'student','2025-12-13 05:38:26',2022),(6,2,8,50,'chapter1-2',NULL,'notes','uploads/teacher_notes/notes/1765769852_OOSE assignment Santosh Baniya.pdf',4,'teacher','2025-12-15 03:37:32',2022);
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notice_read_status`
--

DROP TABLE IF EXISTS `notice_read_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_read_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notice_id` int NOT NULL,
  `student_id` int NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notice_id` (`notice_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notice_read_status`
--

LOCK TABLES `notice_read_status` WRITE;
/*!40000 ALTER TABLE `notice_read_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `notice_read_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notice_type` varchar(50) DEFAULT 'general',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notices`
--

LOCK TABLES `notices` WRITE;
/*!40000 ALTER TABLE `notices` DISABLE KEYS */;
INSERT INTO `notices` VALUES (9,4,NULL,'2021',0,2,'Django Seminar','Dear Students,\r\n\r\nWe are organizing a Django Seminar on 8th December for all Computer Department students (all semesters).\r\nPlease be present in Room 501, Computer Lab at 10:00 AM.\r\n\r\nTopics to be covered:\r\n\r\nDjango Basics\r\nModels and Views\r\nHands-on Mini Project\r\nAttendance is mandatory.\r\n\r\nRegards,\r\nComputer Department Faculty',NULL,1,0,'2025-11-21 06:30:09','internal'),(11,6,NULL,'2021',0,0,'December 8 Exam Notification','Dear Students,\r\n\r\nPlease be informed that the exams will start from December 8, 2025. \r\nMake sure to revise all topics and be prepared. \r\nAttend on time and follow all instructions.\r\n\r\nBest of luck!',NULL,1,0,'2025-11-22 10:55:02','exam'),(12,5,NULL,'2021',7,2,'Semester 7Mid Defence Schedule','Dear Students,\r\n\r\nPlease be informed that the Mid Defence for Semester 7will start from 1st December 2025.\r\nAll students are requested to prepare their presentations and submit the required documents before the scheduled date.\r\nAttend on time and follow all the department guidelines.\r\n\r\nBest regards,\r\nHamro Result',NULL,1,0,'2025-11-22 13:29:21','internal');
/*!40000 ALTER TABLE `notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_verification`
--

DROP TABLE IF EXISTS `otp_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_verification` (
  `email` varchar(100) DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_verification`
--

LOCK TABLES `otp_verification` WRITE;
/*!40000 ALTER TABLE `otp_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `marks_type` varchar(50) NOT NULL,
  `theory` float DEFAULT '0',
  `internal_assignment` float DEFAULT '0',
  `internal_project` float DEFAULT '0',
  `internal_presentation` float DEFAULT '0',
  `internal_other` float DEFAULT '0',
  `internal_total` float DEFAULT '0',
  `practical` float DEFAULT '0',
  `total` float DEFAULT '0',
  `attendance` float DEFAULT '0',
  `total_attendance` float DEFAULT '0',
  `remark` varchar(20) DEFAULT NULL,
  `published` tinyint(1) DEFAULT '0',
  `ut_marks` decimal(5,2) DEFAULT '0.00',
  `assignment` decimal(5,2) DEFAULT '0.00',
  `other` decimal(5,2) DEFAULT '0.00',
  `attendance_marks` decimal(5,2) DEFAULT '0.00',
  `external_marks` decimal(5,2) DEFAULT '0.00',
  `final_total` decimal(5,2) DEFAULT '0.00',
  `status` varchar(10) DEFAULT '',
  `attendance_days` int DEFAULT '0',
  `letter_grade` varchar(3) DEFAULT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL,
  `assessment` float DEFAULT '0',
  `presentation` float DEFAULT '0',
  `total_marks` decimal(5,2) DEFAULT '0.00',
  `pass_marks` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_subject_type` (`student_id`,`subject_id`,`marks_type`)
) ENGINE=MyISAM AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
INSERT INTO `results` VALUES (76,16,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,19.50,0.00,0.00,0.00,0.00,0.00,'FAIL',24,'F',0.00,0,0,50.00,23.00),(75,13,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,29.00,0.00,0.00,0.00,0.00,0.00,'PASS',29,'C-',1.70,0,0,50.00,23.00),(74,14,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,28.00,0.00,0.00,0.00,0.00,0.00,'PASS',28,'C-',1.70,0,0,50.00,23.00),(73,12,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,35.50,0.00,0.00,0.00,0.00,0.00,'PASS',28,'B-',2.70,0,0,50.00,23.00);
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results_backup`
--

DROP TABLE IF EXISTS `results_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results_backup` (
  `id` int NOT NULL DEFAULT '0',
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `marks_type` varchar(50) NOT NULL,
  `theory` float DEFAULT '0',
  `internal_assignment` float DEFAULT '0',
  `internal_project` float DEFAULT '0',
  `internal_presentation` float DEFAULT '0',
  `internal_other` float DEFAULT '0',
  `internal_total` float DEFAULT '0',
  `practical` float DEFAULT '0',
  `total` float DEFAULT '0',
  `attendance` float DEFAULT '0',
  `total_attendance` float DEFAULT '0',
  `remark` varchar(20) DEFAULT NULL,
  `published` tinyint(1) DEFAULT '0',
  `ut_marks` decimal(5,2) DEFAULT '0.00',
  `assignment` decimal(5,2) DEFAULT '0.00',
  `other` decimal(5,2) DEFAULT '0.00',
  `attendance_marks` decimal(5,2) DEFAULT '0.00',
  `external_marks` decimal(5,2) DEFAULT '0.00',
  `final_total` decimal(5,2) DEFAULT '0.00',
  `status` varchar(10) DEFAULT '',
  `attendance_days` int DEFAULT '0',
  `letter_grade` varchar(3) DEFAULT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL,
  `assessment` float DEFAULT '0',
  `presentation` float DEFAULT '0',
  `total_marks` decimal(5,2) DEFAULT '0.00',
  `pass_marks` decimal(5,2) DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results_backup`
--

LOCK TABLES `results_backup` WRITE;
/*!40000 ALTER TABLE `results_backup` DISABLE KEYS */;
INSERT INTO `results_backup` VALUES (57,12,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,34.00,0.00,0.00,0.00,0.00,0.00,'0',28,'C+',2.30,0,0,0.00,0.00),(58,14,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,27.00,0.00,0.00,0.00,0.00,0.00,'0',29,'D+',1.30,0,0,0.00,0.00),(59,13,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,21.00,0.00,0.00,0.00,0.00,0.00,'0',27,'F',0.00,0,0,0.00,0.00),(60,16,10,'Unit Test',0,0,0,0,0,0,0,0,0,30,NULL,0,19.00,0.00,0.00,0.00,0.00,0.00,'0',27,'F',0.00,0,0,0.00,0.00),(61,12,10,'0',0,0,0,0,0,0,0,0,0,30,NULL,0,34.00,0.00,0.00,0.00,0.00,0.00,'PASS',28,'0',2.30,0,0,50.00,22.00),(62,14,10,'0',0,0,0,0,0,0,0,0,0,30,NULL,0,27.00,0.00,0.00,0.00,0.00,0.00,'PASS',29,'0',1.30,0,0,50.00,22.00),(63,13,10,'0',0,0,0,0,0,0,0,0,0,30,NULL,0,21.00,0.00,0.00,0.00,0.00,0.00,'FAIL',27,'0',0.00,0,0,50.00,22.00),(64,16,10,'0',0,0,0,0,0,0,0,0,0,30,NULL,0,19.00,0.00,0.00,0.00,0.00,0.00,'FAIL',27,'0',0.00,0,0,50.00,22.00);
/*!40000 ALTER TABLE `results_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results_publish_status`
--

DROP TABLE IF EXISTS `results_publish_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results_publish_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int DEFAULT NULL,
  `semester_id` int DEFAULT NULL,
  `published` tinyint DEFAULT '0',
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results_publish_status`
--

LOCK TABLES `results_publish_status` WRITE;
/*!40000 ALTER TABLE `results_publish_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `results_publish_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semesters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `semester_order` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `department_idx` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
INSERT INTO `semesters` VALUES (1,2,'1st Semester',1),(2,2,'2nd Semester',2),(3,2,'3rd Semester',3),(4,2,'4th Semester',4),(5,2,'5th Semester',5),(6,2,'6th Semester',6),(7,2,'7th Semester',7),(8,2,'8th Semester',8),(9,3,'1st Semester',1),(10,3,'2nd Semester',2),(11,3,'3rd Semester',3),(12,3,'4th Semester',4),(13,3,'5th Semester',5),(14,3,'6th Semester',6),(15,3,'7th Semester',7),(16,3,'8th Semester',8),(17,4,'1st Semester',1),(18,4,'2nd Semester',2),(19,4,'3rd Semester',3),(20,4,'4th Semester',4),(21,4,'5th Semester',5),(22,4,'6th Semester',6),(23,4,'7th Semester',7),(24,4,'8th Semester',8),(25,4,'9th Semester',9),(26,4,'10th Semester',10),(27,5,'1st Semester',1),(28,5,'2nd Semester',2),(29,5,'3rd Semester',3),(30,5,'4th Semester',4),(31,5,'5th Semester',5),(32,5,'6th Semester',6),(33,5,'7th Semester',7),(34,5,'8th Semester',8);
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_electives`
--

DROP TABLE IF EXISTS `student_electives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_electives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `elective_option_id` int NOT NULL,
  `semester_id` int NOT NULL,
  `department_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`elective_option_id`),
  KEY `elective_option_id` (`elective_option_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_electives`
--

LOCK TABLES `student_electives` WRITE;
/*!40000 ALTER TABLE `student_electives` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_electives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_marks`
--

DROP TABLE IF EXISTS `student_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_marks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `teacher_subject_id` int NOT NULL,
  `marks` float DEFAULT '0',
  `attendance` float DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`teacher_subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_marks`
--

LOCK TABLES `student_marks` WRITE;
/*!40000 ALTER TABLE `student_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `department_id` int DEFAULT NULL,
  `semester_id` int DEFAULT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `batch_year` int DEFAULT NULL,
  `symbol_no` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT 'images/default_user.png',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `semester` tinyint(1) DEFAULT '1',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `symbol_no` (`symbol_no`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (7,'Babita Khadka','khadkababita242@gmail.com',NULL,'9848886006','$2y$10$39qKV08fR.DrXiQxQkB5R.e61/g0mPkB2XLTZLXdduJrBa.DIB/Ae','Civil',5,7,'science and technology',NULL,2021,'21075393','2003-01-21','uploads/1758429092_dubu.jpg','2025-09-19 00:08:38',NULL,NULL,1,7,'active'),(12,'milan khadka','milankhadka612@gmail.com',NULL,'9848589230','$2y$10$UT1kWVgYpKqfl2SQP5zSruiYhyYfEVCoVfp/a4iPAec6VGKxT.vAW','Computer',2,7,'science and technology',NULL,2021,'21075380','2003-01-26','uploads/1763399657_2.webp','2025-10-20 03:29:17',NULL,NULL,1,7,'active'),(13,'santosh baniya','santoshbaniya112@gmail.com',NULL,'980000430','$2y$10$pbENrjTaFmoxJEsgLxpCg.sT1m1ZZftSUbQapQyvrm5oZMTFrmopa','Computer',2,7,'science and technology',NULL,2021,'21075382','2006-03-06','images/default_user.png','2025-10-20 03:32:35',NULL,NULL,1,7,'active'),(14,'sandesh sapkota','sandeshsapkota52@gmail.com',NULL,'98743200','$2y$10$nv2HsA9sJqg234W97Y3luOLcM26vhU4MJX.8M6rXpcX7OIwm8fAuW','Computer',2,7,'science and technology',NULL,2021,'21075379','2005-09-05','images/default_user.png','2025-10-25 02:07:26',NULL,NULL,1,7,'active'),(16,'susmee khadka','sushmakhadka878@gmail.com',NULL,'9848589233','$2y$10$.xlRnCN9cWg6j7RRgc5ITepWGA61KOx0iRzF3Oxc9RMV90njzVpEm','Computer',2,7,'science and technology',NULL,2021,'21075383','2001-11-11','images/default_user.png','2025-10-25 03:22:04',NULL,NULL,1,7,'active'),(18,'Dristy Poudel','poudeldristi0@gmail.com',NULL,'9804156830','$2y$10$Hw6w7PeWEwsXO2BY3M15a.BstNxOi5aNcmfgn.AYijTpsnRhQJ7Oi','Computer',2,NULL,'science and technology',NULL,2021,'21075362','2003-10-20','images/default_user.png','2025-12-01 02:49:03',NULL,NULL,1,8,'active'),(19,'Saurav Ghimire','sauravghimire31@gmail.com',NULL,'9846650930','$2y$10$ysM4ycb8cr9VJNUC16/1se.zWGiidMYfK6w9M52sedyhK5T0E23ZC','Computer',2,NULL,'science and technology',NULL,2021,'21075381','2003-01-14','images/default_user.png','2025-12-01 02:52:44',NULL,NULL,1,8,'active'),(20,'Aneel Chhetri','aneelchhetri962@gmail.com',NULL,'9844901153','$2y$10$HIIDOmXgXr.CbV7guSmM6eDPE3E9tgLqFfuCywhJ/8vArg09djJZa','Computer',2,NULL,'science and technology',NULL,2021,'21075353','2000-06-16','images/default_user.png','2025-12-01 02:56:17',NULL,NULL,1,8,'active'),(21,'Anusha Joshi','bieberdusty76@gmail.com',NULL,'9848589123','$2y$10$.cIAdzybhzBErSlNTAt3Z.K4orxfUdOSqs8LGWpN/f/Bb28qN7ZGe','Computer',2,NULL,'science and technology',NULL,2021,'21075322','2002-10-02','images/default_user.png','2025-12-01 03:02:23',NULL,NULL,1,8,'active'),(23,'Nitesh Acharya','j22366578@gmail.com',NULL,'9848437621','$2y$10$IyvWXnKML.9zC4zeOu9/u.3ZTIJuqYX.tD4w3CG0q9NeiXybZsCzG','Computer',2,NULL,'science and technology',NULL,2021,'21075369','2001-02-06','images/default_user.png','2025-12-01 03:10:22',NULL,NULL,1,8,'active'),(24,'Bishal Lamichhane','vishallamichhane60@gmail.com',NULL,'9806563442','$2y$10$LWcCiYztKiS1aCXb8yVKhuX42rlwtHzYrBNdm5SYPxnm.k62muZ5e','Computer',2,NULL,'science and technology',NULL,2021,'21075359','2001-09-20','images/default_user.png','2025-12-01 03:12:22',NULL,NULL,1,8,'active'),(25,'Chandan Tiwari','chandantiwari9887@gmail.com',NULL,'986574320','$2y$10$IR2xX90vsu35K3PTFGvVdeC3eqSLTLCVkzFrs0s8Bcxod1/Gohw0u','Computer',2,NULL,'science and technology',NULL,2021,'21075360','2001-03-04','images/default_user.png','2025-12-01 03:15:02',NULL,NULL,1,8,'active'),(26,'Ramil lamichhane','ramillamichhane25@gmail.com',NULL,'9803218765','$2y$10$uq2do.kUCqGZySiSzUoDQe54dlRXVOQXJ679.voQHcFd5wYmqC0u6','Computer',2,NULL,'science and technology',NULL,2021,'21075390','2001-10-10','images/default_user.png','2025-12-01 03:17:39',NULL,NULL,1,8,'active');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_assignments`
--

DROP TABLE IF EXISTS `subject_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `department_id` int NOT NULL,
  `semester` int NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `assigned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `result_status` enum('Pending','Published') DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_assignments`
--

LOCK TABLES `subject_assignments` WRITE;
/*!40000 ALTER TABLE `subject_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `department_id` int NOT NULL,
  `semester_id` int NOT NULL,
  `batch_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) NOT NULL,
  `syllabus_type` enum('Old','New') DEFAULT 'New',
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects_department_semester`
--

DROP TABLE IF EXISTS `subjects_department_semester`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects_department_semester` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `department_id` int NOT NULL,
  `semester` int NOT NULL,
  `batch_year` int DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects_department_semester`
--

LOCK TABLES `subjects_department_semester` WRITE;
/*!40000 ALTER TABLE `subjects_department_semester` DISABLE KEYS */;
INSERT INTO `subjects_department_semester` VALUES (21,21,2,7,NULL,NULL),(20,20,2,7,NULL,NULL),(22,22,2,7,NULL,NULL),(23,23,2,7,NULL,NULL),(24,24,2,7,NULL,NULL),(25,25,2,7,NULL,NULL),(48,47,2,8,NULL,NULL),(49,48,2,8,NULL,NULL),(50,49,2,8,NULL,NULL),(51,50,2,8,NULL,NULL),(55,54,2,8,NULL,NULL),(56,55,2,8,NULL,NULL),(58,57,2,8,NULL,NULL),(65,64,2,5,NULL,NULL);
/*!40000 ALTER TABLE `subjects_department_semester` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects_master`
--

DROP TABLE IF EXISTS `subjects_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `semester_id` int DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `credit_hours` decimal(3,1) NOT NULL,
  `is_elective` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects_master`
--

LOCK TABLES `subjects_master` WRITE;
/*!40000 ALTER TABLE `subjects_master` DISABLE KEYS */;
INSERT INTO `subjects_master` VALUES (20,2,7,'Image processing','CMP441',3.0,0),(21,2,7,'Engineering Economics','ECO441',3.0,0),(55,2,8,'Routing and Switching','ELE3',3.0,1),(54,2,8,'Natural language processing','ELE3',3.0,1),(57,2,8,'Cloud Computing','ELE3',3.0,1),(50,2,8,'Digital Signal Analysis and Processing','CMM 442',3.0,0),(49,2,8,'Information Systems','CMP 481',3.0,0),(48,2,8,'Organization and Management','MGT 321',2.0,0),(47,2,8,'Social & Professional Issues in IT','CMP 484',2.0,0),(22,2,7,'Artificial Intelligence','CMP455',3.0,0),(23,2,7,'Computer Network','CMP335',3.0,0),(24,2,7,'ICT Project Management','CMP483',3.0,0),(25,2,7,'Natural Language Processing','CMP451',3.0,1),(64,2,5,'digital logic','dss12',3.0,0);
/*!40000 ALTER TABLE `subjects_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subject_assignments`
--

DROP TABLE IF EXISTS `teacher_subject_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subject_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `department_id` int NOT NULL,
  `semester` int NOT NULL,
  `subject_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `department_id` (`department_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subject_assignments`
--

LOCK TABLES `teacher_subject_assignments` WRITE;
/*!40000 ALTER TABLE `teacher_subject_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_subject_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subjects`
--

DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_map_id` int NOT NULL,
  `mark_lock` tinyint(1) NOT NULL DEFAULT '0',
  `batch_year` int DEFAULT NULL,
  `department_id` int NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `semester_id` int NOT NULL,
  `syllabus_type` enum('Old','New') DEFAULT 'New',
  `total_marks` int DEFAULT '100',
  `pass_marks` int DEFAULT '0',
  `total_attendance` int DEFAULT '50',
  `marks_type` varchar(50) NOT NULL DEFAULT 'Regular',
  `attendance_marks` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_map_id` (`subject_map_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subjects`
--

LOCK TABLES `teacher_subjects` WRITE;
/*!40000 ALTER TABLE `teacher_subjects` DISABLE KEYS */;
INSERT INTO `teacher_subjects` VALUES (15,8,22,0,2021,2,NULL,0,7,'Old',50,22,30,'Unit Test',0),(14,7,24,0,2021,2,NULL,0,7,'Old',50,23,28,'Unit Test',0),(13,6,23,0,2021,2,NULL,0,7,'Old',50,25,29,'Unit Test',0),(12,5,25,0,2021,2,NULL,0,7,'Old',50,23,30,'Unit Test',0),(11,5,20,0,2021,2,NULL,0,7,'Old',50,21,50,'Unit Test',0),(10,4,21,0,2021,2,NULL,0,7,'Old',50,23,30,'Unit Test',0);
/*!40000 ALTER TABLE `teacher_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_hod` tinyint(1) DEFAULT '0',
  `is_verified` tinyint(1) DEFAULT '0',
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_logged_in` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (4,'Ramesh subedi','aahanakhadka3718@gmail.com','$2y$10$wEg6XzNqoC6ADUyj7wwQ0OWfnLnTWRrTA2imLuVTrwgJX/H850LLq','EMP001',NULL,'984800000','uploads/teachers/teacher_4_1760757041.webp',0,1,NULL,NULL,'2025-09-24 15:29:06',0),(5,'Krishna Nath','manandharamisha6@gmail.com','$2y$10$vcjJq2Ty5s7PLwJBsIRkY.aCD1u3KndeQ.Fq9.NK0jqDuVPjWq7oy','EMP002',NULL,'9848580000',NULL,0,1,NULL,NULL,'2025-09-24 15:31:31',0),(6,'Upendra Subedi','manandharamisha03@gmail.com','$2y$10$JvyYwP6bj75Co..l/PuVn.TzeEwZRxMpshxt3.qcUTUD6oPj/I1XC','EMP003',NULL,'984300000',NULL,0,1,NULL,NULL,'2025-09-24 15:33:53',0),(7,'Mahesh sapkota','manandharamisha@gmail.com','$2y$10$xB91JnOTfkw9euZO8xoKjegC.F1S.TMyUrY61VomcHdYIfVDegMby','EMP004',NULL,'98700000',NULL,0,1,NULL,NULL,'2025-09-24 15:41:54',0),(8,'Harikrishna Acharya','ramillamichhane@gmail.com','$2y$10$MiOLpkkqb7VRT1GAhJBkWeyCtbXnBfTNoybN7Gqg4zCYCTPMPRq0u','EMP005',NULL,'983420000',NULL,0,1,NULL,NULL,'2025-10-24 15:58:56',0);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp_results`
--

DROP TABLE IF EXISTS `temp_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `marks_type` enum('UT','Assessment') DEFAULT NULL,
  `unit_test_marks` float DEFAULT NULL,
  `ut_attendance` float DEFAULT NULL,
  `assignment_marks` float DEFAULT NULL,
  `project_marks` float DEFAULT NULL,
  `presentation_marks` float DEFAULT NULL,
  `other_marks` float DEFAULT NULL,
  `semester_attendance` float DEFAULT NULL,
  `total_marks` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp_results`
--

LOCK TABLES `temp_results` WRITE;
/*!40000 ALTER TABLE `temp_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','teacher') NOT NULL DEFAULT 'student',
  `verified` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valid_employee_ids`
--

DROP TABLE IF EXISTS `valid_employee_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `valid_employee_ids` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) DEFAULT NULL,
  `assigned` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valid_employee_ids`
--

LOCK TABLES `valid_employee_ids` WRITE;
/*!40000 ALTER TABLE `valid_employee_ids` DISABLE KEYS */;
INSERT INTO `valid_employee_ids` VALUES (1,'EMP001',1),(2,'EMP002',1),(3,'EMP003',0),(4,'EMP004',0),(5,'EMP005',0),(6,'EMP006',0),(7,'EMP007',0),(8,'EMP008',0),(9,'EMP009',0);
/*!40000 ALTER TABLE `valid_employee_ids` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-17 14:12:58
