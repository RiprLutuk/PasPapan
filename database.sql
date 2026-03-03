-- MySQL dump 10.13  Distrib 9.6.0, for macos26.2 (arm64)
--
-- Host: localhost    Database: absensi
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT 1,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user_created` (`user_id`,`created_at`),
  KEY `idx_activity_ip` (`ip_address`),
  KEY `idx_activity_action` (`action`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_user_dismissals`
--

DROP TABLE IF EXISTS `announcement_user_dismissals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcement_user_dismissals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `dismissed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_user_dismissals_user_id_announcement_id_unique` (`user_id`,`announcement_id`),
  KEY `announcement_user_dismissals_announcement_id_foreign` (`announcement_id`),
  KEY `announcement_user_dismissals_user_id_index` (`user_id`),
  CONSTRAINT `announcement_user_dismissals_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_user_dismissals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_user_dismissals`
--

LOCK TABLES `announcement_user_dismissals` WRITE;
/*!40000 ALTER TABLE `announcement_user_dismissals` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_user_dismissals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `publish_date` date NOT NULL,
  `expire_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` char(26) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `idx_announcements_visibility` (`publish_date`,`expire_date`,`is_active`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `barcode_id` bigint(20) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `latitude_in` double DEFAULT NULL,
  `longitude_in` double DEFAULT NULL,
  `accuracy_in` decimal(10,2) DEFAULT NULL,
  `gps_variance_in` decimal(12,8) DEFAULT NULL,
  `latitude_out` double DEFAULT NULL,
  `longitude_out` double DEFAULT NULL,
  `accuracy_out` decimal(10,2) DEFAULT NULL,
  `gps_variance_out` decimal(12,8) DEFAULT NULL,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `suspicious_reason` varchar(255) DEFAULT NULL,
  `status` enum('present','late','excused','sick','absent','rejected') DEFAULT 'absent',
  `approval_status` varchar(255) NOT NULL DEFAULT 'approved',
  `note` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` char(26) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendances_barcode_id_foreign` (`barcode_id`),
  KEY `attendances_shift_id_foreign` (`shift_id`),
  KEY `attendances_approved_by_foreign` (`approved_by`),
  KEY `idx_attendances_user_date` (`user_id`,`date`),
  KEY `idx_attendances_date_status` (`date`,`status`),
  KEY `idx_attendances_approval_status` (`approval_status`),
  CONSTRAINT `attendances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `attendances_barcode_id_foreign` FOREIGN KEY (`barcode_id`) REFERENCES `barcodes` (`id`),
  CONSTRAINT `attendances_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barcodes`
--

DROP TABLE IF EXISTS `barcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) NOT NULL,
  `latitude` double NOT NULL DEFAULT 0,
  `longitude` double NOT NULL DEFAULT 0,
  `radius` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcodes_value_unique` (`value`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barcodes`
--

LOCK TABLES `barcodes` WRITE;
/*!40000 ALTER TABLE `barcodes` DISABLE KEYS */;
INSERT INTO `barcodes` VALUES (1,'Barcode 1','4664944264452',-30.362428,8.098894,50,'2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `barcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_advances`
--

DROP TABLE IF EXISTS `cash_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `payment_month` int(11) NOT NULL COMMENT 'Month it will be deducted from payroll',
  `payment_year` int(11) NOT NULL COMMENT 'Year it will be deducted from payroll',
  `approved_by` char(26) DEFAULT NULL,
  `head_approved_by` char(26) DEFAULT NULL,
  `head_approved_at` timestamp NULL DEFAULT NULL,
  `finance_approved_by` char(26) DEFAULT NULL,
  `finance_approved_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_advances_user_id_foreign` (`user_id`),
  KEY `cash_advances_approved_by_foreign` (`approved_by`),
  CONSTRAINT `cash_advances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_advances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_advances`
--

LOCK TABLES `cash_advances` WRITE;
/*!40000 ALTER TABLE `cash_advances` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `divisions`
--

DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `divisions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `divisions`
--

LOCK TABLES `divisions` WRITE;
/*!40000 ALTER TABLE `divisions` DISABLE KEYS */;
INSERT INTO `divisions` VALUES (1,'Finance','2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'HR','2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'IT','2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'Operations','2026-03-03 13:35:27','2026-03-03 13:35:27'),(5,'Marketing','2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `divisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `educations`
--

DROP TABLE IF EXISTS `educations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `educations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `educations_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `educations`
--

LOCK TABLES `educations` WRITE;
/*!40000 ALTER TABLE `educations` DISABLE KEYS */;
INSERT INTO `educations` VALUES (1,'SD','2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'SMP','2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'SMA','2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'SMK','2026-03-03 13:35:27','2026-03-03 13:35:27'),(5,'D1','2026-03-03 13:35:27','2026-03-03 13:35:27'),(6,'D2','2026-03-03 13:35:27','2026-03-03 13:35:27'),(7,'D3','2026-03-03 13:35:27','2026-03-03 13:35:27'),(8,'D4','2026-03-03 13:35:27','2026-03-03 13:35:27'),(9,'S1','2026-03-03 13:35:27','2026-03-03 13:35:27'),(10,'S2','2026-03-03 13:35:27','2026-03-03 13:35:27'),(11,'S3','2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `educations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_descriptors`
--

DROP TABLE IF EXISTS `face_descriptors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `face_descriptors` (
  `id` char(26) NOT NULL,
  `user_id` char(26) NOT NULL,
  `descriptor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`descriptor`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `face_descriptors_user_id_unique` (`user_id`),
  CONSTRAINT `face_descriptors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_descriptors`
--

LOCK TABLES `face_descriptors` WRITE;
/*!40000 ALTER TABLE `face_descriptors` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_descriptors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `geolocation_logs`
--

DROP TABLE IF EXISTS `geolocation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geolocation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `action` varchar(255) NOT NULL DEFAULT 'attendance',
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `geolocation_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `geolocation_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `geolocation_logs`
--

LOCK TABLES `geolocation_logs` WRITE;
/*!40000 ALTER TABLE `geolocation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `geolocation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_holidays_date_recurring` (`date`,`is_recurring`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,'2026-01-15','Nyepi','',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'2026-01-01','Tahun Baru 2026 Masehi','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'2026-01-16','Isra Mikraj Nabi Muhammad S.A.W.','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'2026-02-16','Tahun Baru Imlek 2577 Kongzili','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(5,'2026-02-17','Tahun Baru Imlek 2577 Kongzili','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(6,'2026-03-18','Hari Suci Nyepi (Tahun Baru Saka 1948]','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(7,'2026-03-19','Hari Suci Nyepi (Tahun Baru Saka 1948]','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(8,'2026-03-20','Idul Fitri 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(9,'2026-03-21','Idul Fitri 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(10,'2026-03-22','Idul Fitri 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(11,'2026-03-23','Idul Fitri 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(12,'2026-03-24','Idul Fitri 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(13,'2026-04-03','Wafat Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(14,'2026-04-05','Kebangkitan Yesus Kristus (Paskah]','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(15,'2026-05-01','Hari Buruh Internasional','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(16,'2026-05-14','Kenaikan Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(17,'2026-05-15','Kenaikan Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(18,'2026-05-27','Idul Adha 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(19,'2026-05-28','Idul Adha 1447 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(20,'2026-05-31','Hari Raya Waisak 2570 BE','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(21,'2026-06-01','Hari Lahir Pancasila','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(22,'2026-06-16','1 Muharam Tahun Baru Islam 1448 Hijriah','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(23,'2026-08-17','Proklamasi Kemerdekaan','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(24,'2026-08-25','Maulid Nabi Muhammad S.A.W.','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(25,'2026-12-25','Kelahiran Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(26,'2025-01-01','Tahun Baru 2025 Masehi','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(27,'2025-01-27','Isra\' Mi\'raj Nabi Muhammad SAW','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(28,'2025-01-28','Cuti Bersama Tahun Baru Imlek 2576 Kongzili','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(29,'2025-01-29','Tahun Baru Imlek 2576 Kongzili','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(30,'2025-03-28','Cuti Bersama Hari Raya Nyepi Tahun Baru Saka 1947','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(31,'2025-03-29','Hari Raya Nyepi Tahun Baru Saka 1947','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(32,'2025-03-31','Hari Raya Idul Fitri 1446H','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(33,'2025-04-01','Hari Raya Idul Fitri 1446H','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(34,'2025-04-02','Cuti Bersama Hari Raya Idul Fitri 1446H','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(35,'2025-04-03','Cuti Bersama Hari Raya Idul Fitri 1446H','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(36,'2025-04-04','Cuti Bersama Hari Raya Idul Fitri 1446H','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(37,'2025-04-07','Cuti Bersama Hari Raya Idul Fitri 1446H','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(38,'2025-04-18','Wafat Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(39,'2025-04-20','Kebangkitan Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(40,'2025-05-01','Hari Buruh Internasional','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(41,'2025-05-12','Hari Raya Waisak 2569 BE','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(42,'2025-05-13','Cuti Bersama Hari Raya Waisak 2569 BE','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(43,'2025-05-29','Kenaikan Yesus Kristus','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(44,'2025-05-30','Cuti Bersama Kenaikan Yesus Kristus','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(45,'2025-06-01','Hari Lahir Pancasila','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(46,'2025-06-06','Hari Raya Idul Adha 1446H','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(47,'2025-06-09','Cuti Bersama Hari Raya Idul Adha 1446H','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(48,'2025-06-27','Tahun Baru Islam 1 Muharram 1447H','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(49,'2025-08-17','Hari Kemerdekaan Republik Indonesia ke 80','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(50,'2025-08-18','Libur Nasional Kemerdekaan Republik Indonesia','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(51,'2025-09-05','Maulid Nabi Muhammad SAW','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(52,'2025-12-25','Hari Raya Natal','National Holiday',0,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(53,'2025-12-26','Cuti Bersama Hari Raya Natal','Cuti Bersama',0,'2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_levels`
--

DROP TABLE IF EXISTS `job_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `rank` int(11) NOT NULL COMMENT '1: Head, 2: Manager, 3: Senior, 4: Staff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_levels`
--

LOCK TABLES `job_levels` WRITE;
/*!40000 ALTER TABLE `job_levels` DISABLE KEYS */;
INSERT INTO `job_levels` VALUES (1,'Head',1,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'Manager',2,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'Senior',3,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'Staff',4,'2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `job_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_titles`
--

DROP TABLE IF EXISTS `job_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_titles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `job_level_id` bigint(20) unsigned DEFAULT NULL,
  `division_id` bigint(20) unsigned DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 4 COMMENT '1: Head, 2: Manager, 3: Senior, 4: Staff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_titles_name_unique` (`name`),
  KEY `job_titles_job_level_id_foreign` (`job_level_id`),
  KEY `job_titles_division_id_foreign` (`division_id`),
  CONSTRAINT `job_titles_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_titles_job_level_id_foreign` FOREIGN KEY (`job_level_id`) REFERENCES `job_levels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_titles`
--

LOCK TABLES `job_titles` WRITE;
/*!40000 ALTER TABLE `job_titles` DISABLE KEYS */;
INSERT INTO `job_titles` VALUES (1,'Head',1,NULL,4,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'Manager',2,NULL,4,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'Senior',3,NULL,4,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'Staff',4,NULL,4,'2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `job_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `license_purchases`
--

DROP TABLE IF EXISTS `license_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `license_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) NOT NULL,
  `license_key` text NOT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `expires_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `license_purchases`
--

LOCK TABLES `license_purchases` WRITE;
/*!40000 ALTER TABLE `license_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `license_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_divisions_table',1),(2,'0001_01_01_000000_create_educations_table',1),(3,'0001_01_01_000000_create_job_titles_table',1),(4,'0001_01_01_000000_create_users_table',1),(5,'0001_01_01_000001_add_two_factor_columns_to_users_table',1),(6,'0001_01_01_000001_create_cache_table',1),(7,'0001_01_01_000002_create_jobs_table',1),(8,'2024_06_08_023152_create_personal_access_tokens_table',1),(9,'2024_06_09_113236_create_barcodes_table',1),(10,'2024_06_16_092112_create_shifts_table',1),(11,'2024_06_17_113814_create_attendances_table',1),(12,'2024_12_11_000000_create_geolocation_logs_table',1),(13,'2024_12_11_000001_add_photo_to_attendances_table',1),(14,'2025_12_16_114405_add_location_history_to_attendances_table',1),(15,'2026_01_01_140536_create_activity_logs_table',1),(16,'2026_01_02_141019_create_schedules_table',1),(17,'2026_01_02_233117_add_count_to_activity_logs_table',1),(18,'2026_01_05_125552_create_settings_table',1),(19,'2026_01_06_073719_add_language_to_users_table',1),(20,'2026_01_06_075014_add_approval_columns_to_attendances_table',1),(21,'2026_01_06_092423_create_notifications_table',1),(22,'2026_01_07_080158_update_attendances_status_enum',1),(23,'2026_01_09_084618_add_performance_indexes',1),(24,'2026_01_09_084644_create_announcements_table',1),(25,'2026_01_09_084644_create_holidays_table',1),(26,'2026_01_09_100249_add_new_settings_leave_notification_attendance',1),(27,'2026_01_09_113207_create_announcement_user_dismissals_table',1),(28,'2026_01_09_212712_create_reimbursements_table',1),(29,'2026_01_10_105710_add_level_to_job_titles_table',1),(30,'2026_01_10_110830_create_job_levels_table',1),(31,'2026_01_10_110831_add_relations_to_job_titles_table',1),(32,'2026_01_10_152747_add_approved_by_to_attendances_and_reimbursements',1),(33,'2026_01_20_193021_create_overtimes_table',1),(34,'2026_01_20_200304_add_basic_salary_to_users_table',1),(35,'2026_01_20_200305_create_payrolls_table',1),(36,'2026_01_20_204550_create_payroll_components_and_update_payrolls_table',1),(37,'2026_01_20_210602_add_payslip_password_to_users_table',1),(38,'2026_01_20_225836_create_face_descriptors_table',1),(39,'2026_01_21_000001_add_enterprise_license_key_to_settings',1),(40,'2026_01_21_183548_add_gps_accuracy_columns_to_attendances_table',1),(41,'2026_01_23_101516_change_time_columns_to_datetime_in_attendances_and_overtimes_tables',1),(42,'2026_01_24_092646_create_demo_admin_user',1),(43,'2026_01_24_100000_update_demo_users',1),(44,'2026_02_25_000001_create_license_purchases_table',1),(45,'2026_03_01_192318_create_cash_advances_table',1),(46,'2026_03_03_092201_create_wilayah_table',1),(47,'2026_03_03_092202_add_wilayah_to_users_table',1),(48,'2026_03_03_094228_drop_city_from_users_table',1),(49,'2026_03_03_124505_add_double_approval_to_finance_requests',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtimes`
--

DROP TABLE IF EXISTS `overtimes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtimes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `date` date NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'in minutes',
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by` char(26) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overtimes_user_id_foreign` (`user_id`),
  KEY `overtimes_approved_by_foreign` (`approved_by`),
  CONSTRAINT `overtimes_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `overtimes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtimes`
--

LOCK TABLES `overtimes` WRITE;
/*!40000 ALTER TABLE `overtimes` DISABLE KEYS */;
/*!40000 ALTER TABLE `overtimes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_components`
--

DROP TABLE IF EXISTS `payroll_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('allowance','deduction') NOT NULL,
  `calculation_type` enum('fixed','percentage_basic','daily_presence') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL COMMENT 'Fixed amount or daily rate',
  `percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage (0-100)',
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_components`
--

LOCK TABLES `payroll_components` WRITE;
/*!40000 ALTER TABLE `payroll_components` DISABLE KEYS */;
INSERT INTO `payroll_components` VALUES (1,'Uang Makan','allowance','daily_presence',50000.00,NULL,0,1,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'Uang Transport','allowance','daily_presence',25000.00,NULL,0,1,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'Tunjangan Kesehatan','allowance','fixed',150000.00,NULL,1,1,'2026-03-03 13:35:27','2026-03-03 13:35:27'),(4,'PPh 21 (Simulasi)','deduction','percentage_basic',NULL,5.00,0,1,'2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `payroll_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrolls`
--

DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payrolls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `type` enum('regular','special') NOT NULL DEFAULT 'regular',
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allowances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances`)),
  `deductions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions`)),
  `overtime_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of calculation details' CHECK (json_valid(`details`)),
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `generated_by` char(26) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payrolls_user_id_month_year_unique` (`user_id`,`month`,`year`),
  KEY `payrolls_generated_by_foreign` (`generated_by`),
  CONSTRAINT `payrolls_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payrolls_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reimbursements`
--

DROP TABLE IF EXISTS `reimbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reimbursements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `description` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` char(26) DEFAULT NULL,
  `head_approved_by` char(26) DEFAULT NULL,
  `head_approved_at` timestamp NULL DEFAULT NULL,
  `finance_approved_by` char(26) DEFAULT NULL,
  `finance_approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reimbursements_user_id_foreign` (`user_id`),
  KEY `reimbursements_approved_by_foreign` (`approved_by`),
  CONSTRAINT `reimbursements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reimbursements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursements`
--

LOCK TABLES `reimbursements` WRITE;
/*!40000 ALTER TABLE `reimbursements` DISABLE KEYS */;
/*!40000 ALTER TABLE `reimbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `is_off` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedules_user_id_date_unique` (`user_id`,`date`),
  KEY `schedules_shift_id_foreign` (`shift_id`),
  CONSTRAINT `schedules_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` char(26) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'leave.annual_quota','12','leave','number','Jatah Cuti Tahunan (hari)','2026-03-03 13:35:24','2026-03-03 13:35:24'),(2,'leave.sick_quota','14','leave','number','Jatah Sakit per Tahun (hari)','2026-03-03 13:35:24','2026-03-03 13:35:24'),(3,'leave.require_attachment','0','leave','boolean','Wajib Lampiran untuk Pengajuan Cuti/Sakit','2026-03-03 13:35:24','2026-03-03 13:35:27'),(4,'leave.auto_approve_days','3','leave','number','Auto-Approve jika tidak diproses dalam X hari (0 = disabled)','2026-03-03 13:35:24','2026-03-03 13:35:24'),(5,'notif.admin_email','example@gmail.com','notification','text','Email Admin untuk Notifikasi (kosongkan jika tidak ada)','2026-03-03 13:35:24','2026-03-03 13:35:27'),(6,'attendance.work_hours_per_day','8','attendance','number','Jam Kerja per Hari','2026-03-03 13:35:24','2026-03-03 13:35:24'),(7,'enterprise_license_key','','enterprise','textarea','Enterprise License Key','2026-03-03 13:35:25','2026-03-03 13:35:27'),(8,'security.rate_limit_global','1000','security','number','Global API rate limit per minute','2026-03-03 13:35:27','2026-03-03 13:35:27'),(9,'security.rate_limit_login','5','security','number','Login rate limit per minute','2026-03-03 13:35:27','2026-03-03 13:35:27'),(10,'attendance.grace_period','10','attendance','number','Late Grace Period (minutes)','2026-03-03 13:35:27','2026-03-03 13:35:27'),(11,'app.name','PasPapan','identity','text','Application Name','2026-03-03 13:35:27','2026-03-03 13:35:27'),(12,'app.company_name','PT. PasPapan Indonesia','identity','text','Company Name for Reports','2026-03-03 13:35:27','2026-03-03 13:35:27'),(13,'app.support_contact','example@gmail.com','identity','text','Support Email/Phone','2026-03-03 13:35:27','2026-03-03 13:35:27'),(14,'feature.require_photo','1','features','boolean','Require Photo for Attendance','2026-03-03 13:35:27','2026-03-03 13:35:27'),(15,'app.maintenance_mode','0','features','boolean','Enable Maintenance Mode','2026-03-03 13:35:27','2026-03-03 13:35:27'),(16,'app.time_format','24','general','select','Time Format (12h/24h)','2026-03-03 13:35:27','2026-03-03 13:35:27'),(17,'app.show_seconds','0','general','boolean','Show Seconds in Time Display','2026-03-03 13:35:27','2026-03-03 13:35:27'),(18,'app.company_address','Jalan example, example, example, example, example','identity','textarea','Company Address','2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES (1,'Shift Pagi','07:00:00','15:00:00','2026-03-03 13:35:27','2026-03-03 13:35:27'),(2,'Shift Malam','23:00:00','07:00:00','2026-03-03 13:35:27','2026-03-03 13:35:27'),(3,'Shift Sore','15:00:00','23:00:00','2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(26) NOT NULL,
  `nip` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `hourly_rate` decimal(15,2) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'id',
  `phone` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `provinsi_kode` varchar(13) DEFAULT NULL,
  `kabupaten_kode` varchar(13) DEFAULT NULL,
  `kecamatan_kode` varchar(13) DEFAULT NULL,
  `kelurahan_kode` varchar(13) DEFAULT NULL,
  `education_id` bigint(20) unsigned DEFAULT NULL,
  `division_id` bigint(20) unsigned DEFAULT NULL,
  `job_title_id` bigint(20) unsigned DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `payslip_password` varchar(255) DEFAULT NULL,
  `payslip_password_set_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `raw_password` varchar(255) DEFAULT NULL,
  `group` enum('user','admin','superadmin') NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_education_id_foreign` (`education_id`),
  KEY `users_division_id_foreign` (`division_id`),
  KEY `users_job_title_id_foreign` (`job_title_id`),
  KEY `idx_users_birth_date` (`birth_date`),
  CONSTRAINT `users_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  CONSTRAINT `users_education_id_foreign` FOREIGN KEY (`education_id`) REFERENCES `educations` (`id`),
  CONSTRAINT `users_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('01kjsyj8c87wsyrxm11t7y2252',NULL,'Demo Admin',NULL,NULL,'admin123@paspapan.com','id','081234567801','male',NULL,NULL,'Demo Address Admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$opLGummyAaP5bQa0mfg67eFy8X8cGTEBLeFTNAGpfHcND4dy3FxTe',NULL,NULL,NULL,NULL,NULL,NULL,'admin','2026-03-03 13:35:26',NULL,NULL,'2026-03-03 13:35:26','2026-03-03 13:35:26'),('01kjsyj8k7rztsfha1s253rnr8',NULL,'Demo User',NULL,NULL,'user123@paspapan.com','id','081234567802','male',NULL,NULL,'Demo Address User',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$EAsj2qopai1Qz8tfing9juTx1Q.NAf6qiZ2air3Cn7BP8SZZC6ixu',NULL,NULL,NULL,NULL,NULL,NULL,'user','2026-03-03 13:35:26',NULL,NULL,'2026-03-03 13:35:26','2026-03-03 13:35:26'),('01kjsyj9x5003fmamzybenbpp4','0000000000000000','Super Admin',NULL,NULL,'superadmin@example.com','id','00000000000','male',NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$lXOZRabuPDYN6A/CwPsS/.cgBLafDXGpk/xE18Ch8m3fw22FZggHu',NULL,NULL,NULL,NULL,NULL,NULL,'superadmin','2026-03-03 13:35:27',NULL,'1S1w1eGvEx','2026-03-03 13:35:27','2026-03-03 13:35:27'),('01kjsyja410722t9213q6fe5j0','0000000000000000','Admin',NULL,NULL,'admin@example.com','id','00000000000','male',NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$HhkD2WAHwpErvXHXEWXFCOt5dWgc1mIDGfC3karrOLnbnqEtYmGVW',NULL,NULL,NULL,NULL,NULL,NULL,'admin','2026-03-03 13:35:27',NULL,'EAR2IoZOb4','2026-03-03 13:35:27','2026-03-03 13:35:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wilayah`
--

DROP TABLE IF EXISTS `wilayah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wilayah` (
  `kode` varchar(13) NOT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`kode`),
  KEY `wilayah_nama_index` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wilayah`
--

LOCK TABLES `wilayah` WRITE;
/*!40000 ALTER TABLE `wilayah` DISABLE KEYS */;
/*!40000 ALTER TABLE `wilayah` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-03 20:35:28
