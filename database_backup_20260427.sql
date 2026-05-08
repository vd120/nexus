/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: nexus_socket
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-5 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_action_index` (`user_id`,`action`),
  KEY `activity_logs_logged_at_index` (`logged_at`),
  KEY `activity_logs_session_id_index` (`session_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(1,NULL,'kNZRJR9SaiE0dNca0PZhGLKZqOGOKe0em2nOjCE2','failed_login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 22:59:28','2026-04-21 22:59:28',NULL),
(2,3,'3UBtcpueDnst3Dzue7BWfSkmlpAiBOc3eo0aCTkN','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 22:59:54','2026-04-21 22:59:54',NULL),
(3,12,'PmE1ZpSyDB4Czr51tnPw5vwfHS84bRCRor69fBQX','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 23:04:32','2026-04-21 23:04:32',NULL),
(4,12,'PmE1ZpSyDB4Czr51tnPw5vwfHS84bRCRor69fBQX','logout','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 23:04:34','2026-04-21 23:04:34',NULL),
(5,12,'EVEaCYlpX8xUrNBz3cCd8vcth8mI8YEIGGbEft9l','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 23:06:23','2026-04-21 23:06:23',NULL),
(6,3,'3RRPQaWXmezwa3pOekztHMVcf9jBtLoT0WPKPoS5','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-21 23:13:32','2026-04-21 23:13:32',NULL),
(7,3,'gn8FFowWQ5abAKJr7XXJ6WsIzK4CPxaEoiBBUrAf','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 10:30:48','2026-04-22 10:30:48',NULL),
(8,12,'TqamFZNTg7ptkxHreCjo895u8UBTSpPnpyo7LSOk','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 10:33:27','2026-04-22 10:33:27',NULL),
(9,3,'WKrfWb0oKwUcqI0t1e1ANEtsTnSKfmSfuev1UyxB','logout','127.0.0.1','Symfony','desktop','Other','Unknown',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 12:49:57','2026-04-22 12:49:57',NULL),
(10,3,'8YLjFgKfmPCTlOfFoKfRuE6UuEUKXDsuvPlQNhbj','logout','127.0.0.1','Symfony','desktop','Other','Unknown',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 12:50:19','2026-04-22 12:50:19',NULL),
(11,3,'2qAuoKCLcFSOgUKShysamtDel6QuzU9hE7HPLu59','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 14:17:25','2026-04-22 14:17:25',NULL),
(12,12,'HmXXYzqljYfpR0WrwENgXETyEjXdOaT4lIoEWZvj','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 14:23:10','2026-04-22 14:23:10',NULL),
(13,12,'HmXXYzqljYfpR0WrwENgXETyEjXdOaT4lIoEWZvj','logout','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 16:16:49','2026-04-22 16:16:49',NULL),
(14,12,'jp8NZHEVevnq9BehVG9v18X3FPZk03GGmig5ki6P','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-22 16:29:18','2026-04-22 16:29:18',NULL),
(15,3,'yTL7ElB2VW7Wflny9cjp1SOCvLvM3U0gZTFZLbZy','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 07:57:03','2026-04-23 07:57:03',NULL),
(16,12,'p6GT7yjnxIIDvWyEjgnVvzoNk6jOoJuaZX5O3BRx','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 08:44:11','2026-04-23 08:44:11',NULL),
(17,3,'Eg3aVgLZ7B13tBZkFFVipT2GRH6bCNGO543MnGNU','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 12:05:41','2026-04-23 12:05:41',NULL),
(18,12,'2USjMmD79bxDL7rP1JmXyPNeX2OtaQGHb5YskLVz','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 12:05:51','2026-04-23 12:05:51',NULL),
(19,12,'wi8kk9Joy52A9g2fUDEvQmQA4tRj8UdbD97C8rmT','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 16:18:58','2026-04-23 16:18:58',NULL),
(20,3,'66QT9v1ToJET4q4E7UeBlKo05IXb1linO17NADxq','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 16:19:49','2026-04-23 16:19:49',NULL),
(21,3,'fMa959aUfRdQekKhitRCA6QVklaFmnY1f9xZkdK7','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 23:00:13','2026-04-23 23:00:13',NULL),
(22,12,'iiddLDOl4XV3soT2wqvtVV5XcZijlj7FhiL5MLU7','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 23:12:55','2026-04-23 23:12:55',NULL),
(23,3,'BTgpUhC3SMgTbphA9iffDVlGhlNB3I7c4Wu9PQEd','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','desktop','Chrome 147','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 23:21:46','2026-04-23 23:21:46',NULL),
(24,3,'PsPm08J366F2UM3P9T7SbhzkmTIPyNZ5lRnccJvy','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','desktop','Chrome 147','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-24 08:50:30','2026-04-24 08:50:30',NULL),
(25,12,'JWpoJYqNmYl3A9Zk6E2XANl0Zuky4irIUrZCva49','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-24 10:19:55','2026-04-24 10:19:55',NULL),
(26,3,'piBpO09XcZd18KIxY2O2bzI57qAwu9uwVOKa7eM8','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-24 10:39:32','2026-04-24 10:39:33',NULL),
(27,3,'9llxCuRm6yupcCG5Mn0y5vQlOeNcJl26Qm82mDsl','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-24 15:59:23','2026-04-24 15:59:23',NULL),
(28,12,'1Z3ENvIs8IER4ZRSNERzDHVjYFctGOmwOiW2OEUc','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-24 16:00:43','2026-04-24 16:00:43',NULL),
(29,3,'cHKTxZE2Z2ACadcgDawcpOcT3krmfVi0yoR769Vo','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-25 07:58:51','2026-04-25 07:58:51',NULL),
(30,12,'DBBvOEKsVkYdKurHwzp3L0RevBmCnrEOWZ1cdQhD','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-25 08:51:36','2026-04-25 08:51:36',NULL),
(31,3,'5SklewXWDsNRoPtf0RRZ7k9Dgfqr9z6tJraFPVMg','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-26 12:09:48','2026-04-26 12:09:48',NULL),
(32,12,'6d1QH94Zi9NuUMFBCSRR8GH4y23gVf1Uhq5WIelh','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-26 12:47:55','2026-04-26 12:47:55',NULL),
(33,12,'bxbXe9E9Mc8F5FOiMZ6Lrb9wTUjixGpPL96N5kvd','login','192.168.1.12','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','desktop','Chrome 147','Windows 10/11',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-26 17:38:10','2026-04-26 17:38:10',NULL),
(34,12,'sc6dTsa1rZTUKufr6m89ZVxf6jybSI7TOtznGNy0','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','desktop','Chrome 147','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-26 19:14:32','2026-04-26 19:14:32',NULL),
(35,3,'C8EBHnTBPLs9cMNtOunYJbe13yVWo6iGYM12k1BM','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/999.0.0.0 Safari/537.36','desktop','Chrome 999','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 09:12:46','2026-04-27 09:12:46',NULL),
(36,12,'sWsoebPHoO7hHBjUKBvnR2ufVXTrQtuRPkJTJ91d','login','192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','mobile','Chrome 147','Android 10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 09:16:27','2026-04-27 09:16:27',NULL),
(37,3,'Tjb7c6jvcuQKpWoEMiLJ9ubfRPCjkcvQC0VJYQco','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/999.0.0.0 Safari/537.36','desktop','Chrome 999','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:05:02','2026-04-27 11:05:02',NULL),
(38,3,'qLi0HQj22lsdPCRofQclA9qfXUmnEeyYhBrf5NjM','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:21:56','2026-04-27 11:21:56',NULL),
(39,3,'qLi0HQj22lsdPCRofQclA9qfXUmnEeyYhBrf5NjM','profile_update','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:25:03','2026-04-27 11:25:03',NULL),
(40,3,'qLi0HQj22lsdPCRofQclA9qfXUmnEeyYhBrf5NjM','profile_update','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:25:15','2026-04-27 11:25:15',NULL),
(41,3,'qLi0HQj22lsdPCRofQclA9qfXUmnEeyYhBrf5NjM','profile_update','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','desktop','Firefox 149','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:34:18','2026-04-27 11:34:18',NULL),
(42,13,'etrjKTiFQp54gTTUBs7w7ZBT6k9Mmxkuf7BuOZK6','login','192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','desktop','Chrome 147','Linux',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 12:39:01','2026-04-27 12:39:01',NULL);
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blocker_id` bigint(20) unsigned NOT NULL,
  `blocked_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocks_blocker_id_blocked_id_unique` (`blocker_id`,`blocked_id`),
  KEY `blocks_blocked_id_foreign` (`blocked_id`),
  CONSTRAINT `blocks_blocked_id_foreign` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blocks_blocker_id_foreign` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `comment_likes`
--

DROP TABLE IF EXISTS `comment_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `comment_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_likes_user_id_comment_id_unique` (`user_id`,`comment_id`),
  KEY `comment_likes_comment_id_foreign` (`comment_id`),
  CONSTRAINT `comment_likes_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comment_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_likes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `comment_likes` WRITE;
/*!40000 ALTER TABLE `comment_likes` DISABLE KEYS */;
INSERT INTO `comment_likes` VALUES
(1,6,4,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(2,5,6,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(3,10,7,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(4,11,10,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(5,10,12,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(6,7,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(7,8,17,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(8,4,24,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(9,5,27,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(10,6,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(11,5,30,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(12,4,32,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(13,11,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(14,5,42,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(15,10,43,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(16,10,45,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(17,5,46,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(18,5,47,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(19,6,49,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(20,4,52,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(21,8,53,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(22,11,55,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(23,6,57,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(24,4,60,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(25,7,61,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(26,7,63,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(27,5,67,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(28,5,69,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(29,5,70,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(30,5,71,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(31,11,72,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(32,3,75,'2026-04-21 23:17:05','2026-04-21 23:17:05'),
(43,3,82,'2026-04-26 21:01:50','2026-04-26 21:01:50');
/*!40000 ALTER TABLE `comment_likes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_parent_id_foreign` (`parent_id`),
  KEY `comments_post_id_created_at_index` (`post_id`,`created_at`),
  KEY `comments_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `comments_post_id_index` (`post_id`),
  KEY `comments_user_id_index` (`user_id`),
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES
(1,11,3,NULL,'Love this! ❤️','2026-04-04 22:59:48','2026-04-21 22:59:48'),
(2,9,4,NULL,'Great post! 👏','2026-04-18 22:59:48','2026-04-21 22:59:48'),
(3,7,5,NULL,'This made my day! 😊','2026-04-08 22:59:48','2026-04-21 22:59:48'),
(4,5,7,NULL,'So inspiring! 🙌','2026-04-06 22:59:48','2026-04-21 22:59:48'),
(5,7,8,NULL,'This is exactly what I needed to see today!','2026-04-06 22:59:48','2026-04-21 22:59:48'),
(6,8,8,NULL,'This made my day! 😊','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(7,8,9,NULL,'This is exactly what I needed to see today!','2026-04-12 22:59:48','2026-04-21 22:59:48'),
(8,10,9,NULL,'Thanks for sharing!','2026-04-16 22:59:48','2026-04-21 22:59:48'),
(9,11,10,NULL,'Amazing! Keep it up!','2026-04-04 22:59:48','2026-04-21 22:59:48'),
(10,6,10,NULL,'Love this! ❤️','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(11,9,11,NULL,'Well said! 💯','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(12,9,11,NULL,'Thanks for sharing!','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(13,11,12,NULL,'This made my day! 😊','2026-04-06 22:59:48','2026-04-21 22:59:48'),
(14,4,13,NULL,'This is exactly what I needed to see today!','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(15,5,13,NULL,'So inspiring! 🙌','2026-04-05 22:59:48','2026-04-21 22:59:48'),
(16,7,13,NULL,'This made my day! 😊','2026-04-09 22:59:48','2026-04-21 22:59:48'),
(17,11,13,NULL,'Well said! 💯','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(18,7,14,NULL,'This is exactly what I needed to see today!','2026-04-05 22:59:48','2026-04-21 22:59:48'),
(19,7,15,NULL,'Beautiful! 😍','2026-04-10 22:59:48','2026-04-21 22:59:48'),
(20,9,15,NULL,'Couldn\'t agree more!','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(21,5,16,NULL,'So inspiring! 🙌','2026-04-02 22:59:48','2026-04-21 22:59:48'),
(22,11,16,NULL,'Love this! ❤️','2026-04-20 22:59:48','2026-04-21 22:59:48'),
(23,10,17,NULL,'Beautiful! 😍','2026-04-16 22:59:48','2026-04-21 22:59:48'),
(24,9,17,NULL,'Love this! ❤️','2026-04-09 22:59:48','2026-04-21 22:59:48'),
(25,9,17,NULL,'Couldn\'t agree more!','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(26,9,18,NULL,'Thanks for sharing!','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(27,4,19,NULL,'Love this! ❤️','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(28,4,19,NULL,'Couldn\'t agree more!','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(29,10,19,NULL,'Amazing! Keep it up!','2026-04-09 22:59:48','2026-04-21 22:59:48'),
(30,6,19,NULL,'Amazing! Keep it up!','2026-04-11 22:59:48','2026-04-21 22:59:48'),
(31,4,21,NULL,'Well said! 💯','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(32,11,21,NULL,'Beautiful! 😍','2026-04-13 22:59:48','2026-04-21 22:59:48'),
(33,7,22,NULL,'Love this! ❤️','2026-04-04 22:59:48','2026-04-21 22:59:48'),
(34,7,22,NULL,'Beautiful! 😍','2026-04-10 22:59:48','2026-04-21 22:59:48'),
(35,9,22,NULL,'Great post! 👏','2026-04-07 22:59:48','2026-04-21 22:59:48'),
(36,7,23,NULL,'So inspiring! 🙌','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(37,10,24,NULL,'Great post! 👏','2026-04-10 22:59:48','2026-04-21 22:59:48'),
(38,5,24,NULL,'Well said! 💯','2026-04-10 22:59:48','2026-04-21 22:59:48'),
(39,5,24,NULL,'Couldn\'t agree more!','2026-04-05 22:59:48','2026-04-21 22:59:48'),
(40,9,24,NULL,'This made my day! 😊','2026-04-16 22:59:48','2026-04-21 22:59:48'),
(41,4,26,NULL,'So inspiring! 🙌','2026-04-12 22:59:48','2026-04-21 22:59:48'),
(42,11,26,NULL,'Couldn\'t agree more!','2026-04-14 22:59:48','2026-04-21 22:59:48'),
(43,7,28,NULL,'Thanks for sharing!','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(44,9,28,NULL,'So inspiring! 🙌','2026-04-20 22:59:48','2026-04-21 22:59:48'),
(45,11,28,NULL,'Thanks for sharing!','2026-04-05 22:59:48','2026-04-21 22:59:48'),
(46,7,28,NULL,'Couldn\'t agree more!','2026-04-13 22:59:48','2026-04-21 22:59:48'),
(47,8,29,NULL,'So inspiring! 🙌','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(48,4,29,NULL,'Great post! 👏','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(49,4,30,NULL,'This made my day! 😊','2026-04-07 22:59:48','2026-04-21 22:59:48'),
(50,7,30,NULL,'Love this! ❤️','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(51,11,31,NULL,'Thanks for sharing!','2026-04-16 22:59:48','2026-04-21 22:59:48'),
(52,6,31,NULL,'This is exactly what I needed to see today!','2026-04-05 22:59:48','2026-04-21 22:59:48'),
(53,4,31,NULL,'So inspiring! 🙌','2026-04-14 22:59:48','2026-04-21 22:59:48'),
(54,7,31,NULL,'Great post! 👏','2026-04-03 22:59:48','2026-04-21 22:59:48'),
(55,9,32,NULL,'This is exactly what I needed to see today!','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(56,11,32,NULL,'Beautiful! 😍','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(57,7,32,NULL,'Well said! 💯','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(58,6,33,NULL,'This is exactly what I needed to see today!','2026-04-14 22:59:48','2026-04-21 22:59:48'),
(59,7,33,NULL,'Thanks for sharing!','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(60,6,33,NULL,'Thanks for sharing!','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(61,10,34,NULL,'Love this! ❤️','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(62,9,34,NULL,'Great post! 👏','2026-04-14 22:59:48','2026-04-21 22:59:48'),
(63,11,34,NULL,'So inspiring! 🙌','2026-04-14 22:59:48','2026-04-21 22:59:48'),
(64,4,34,NULL,'Beautiful! 😍','2026-04-15 22:59:48','2026-04-21 22:59:48'),
(65,9,35,NULL,'Love this! ❤️','2026-04-20 22:59:48','2026-04-21 22:59:48'),
(66,10,36,NULL,'Couldn\'t agree more!','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(67,11,36,NULL,'Great post! 👏','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(68,8,36,NULL,'Thanks for sharing!','2026-04-20 22:59:48','2026-04-21 22:59:48'),
(69,10,36,NULL,'Beautiful! 😍','2026-04-17 22:59:48','2026-04-21 22:59:48'),
(70,4,37,NULL,'So inspiring! 🙌','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(71,11,37,NULL,'Great post! 👏','2026-04-10 22:59:48','2026-04-21 22:59:48'),
(72,5,38,NULL,'Amazing! Keep it up!','2026-04-19 22:59:48','2026-04-21 22:59:48'),
(73,9,38,NULL,'Thanks for sharing!','2026-04-04 22:59:48','2026-04-21 22:59:48'),
(74,7,38,NULL,'This is exactly what I needed to see today!','2026-04-16 22:59:48','2026-04-21 22:59:48'),
(75,3,41,NULL,'شيص','2026-04-21 23:17:01','2026-04-21 23:17:01'),
(76,3,41,NULL,'شيص','2026-04-21 23:18:15','2026-04-21 23:18:15'),
(77,3,41,NULL,'شيص','2026-04-21 23:18:24','2026-04-21 23:18:24'),
(78,3,41,NULL,'awd','2026-04-21 23:19:59','2026-04-21 23:19:59'),
(79,3,41,NULL,'awd','2026-04-21 23:20:12','2026-04-21 23:20:12'),
(80,3,41,NULL,'3f','2026-04-21 23:20:16','2026-04-21 23:20:16'),
(81,3,41,76,'awd','2026-04-21 23:20:22','2026-04-21 23:20:22'),
(82,12,41,NULL,'يتتث','2026-04-21 23:20:41','2026-04-21 23:20:41'),
(83,12,41,80,'uu','2026-04-22 10:33:44','2026-04-22 10:33:44'),
(84,12,41,80,'jjss','2026-04-22 10:36:44','2026-04-22 10:36:44'),
(85,12,41,80,'he','2026-04-22 11:07:50','2026-04-22 11:07:50');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `conversation_mutes`
--

DROP TABLE IF EXISTS `conversation_mutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_mutes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_mutes_user_id_conversation_id_unique` (`user_id`,`conversation_id`),
  KEY `conversation_mutes_conversation_id_foreign` (`conversation_id`),
  CONSTRAINT `conversation_mutes_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_mutes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_mutes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `conversation_mutes` WRITE;
/*!40000 ALTER TABLE `conversation_mutes` DISABLE KEYS */;
INSERT INTO `conversation_mutes` VALUES
(7,3,16,'2026-04-27 16:33:50','2026-04-27 16:33:50');
/*!40000 ALTER TABLE `conversation_mutes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(32) NOT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `user1_id` bigint(20) unsigned NOT NULL,
  `user2_id` bigint(20) unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversations_user1_id_user2_id_unique` (`user1_id`,`user2_id`),
  UNIQUE KEY `conversations_slug_unique` (`slug`),
  KEY `conversations_user2_id_foreign` (`user2_id`),
  KEY `conversations_last_message_at_index` (`last_message_at`),
  KEY `conversations_group_id_foreign` (`group_id`),
  CONSTRAINT `conversations_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_user1_id_foreign` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_user2_id_foreign` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES
(2,'EDkkpASroRdRUGBrq57Ga8yn',0,NULL,NULL,NULL,4,5,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(3,'Dthb1pvCslUz5RKBNygzMf6k',0,NULL,NULL,NULL,5,6,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:49'),
(4,'fCqvkVYzQOzNxRnEX7oRG691',0,NULL,NULL,NULL,6,7,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49'),
(13,'PHDJUJKnaCv4TOKGc8omDEmd',0,NULL,NULL,NULL,3,12,'2026-04-27 12:37:22','2026-04-24 19:08:55','2026-04-27 12:37:22'),
(16,'f5hywm5zCdeznCqrHEgCr7Xf',1,7,'awd',NULL,3,NULL,'2026-04-27 16:34:05','2026-04-27 12:10:08','2026-04-27 16:45:36'),
(18,'XfiYYaPsGmKFl7wOU1TPvUZE',0,NULL,NULL,NULL,12,13,'2026-04-27 12:47:43','2026-04-27 12:47:30','2026-04-27 12:47:43'),
(19,'qXri6ehHl9vVhgeU7JMzk6vd',0,NULL,NULL,NULL,3,13,'2026-04-27 13:00:18','2026-04-27 12:47:58','2026-04-27 13:00:18');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `event_reactions`
--

DROP TABLE IF EXISTS `event_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `reaction_type` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_reactions_user_id_event_id_unique` (`user_id`,`event_id`),
  KEY `event_reactions_event_id_reaction_type_index` (`event_id`,`reaction_type`),
  CONSTRAINT `event_reactions_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_reactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `event_reactions` WRITE;
/*!40000 ALTER TABLE `event_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_reactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `slug` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `year` year(4) DEFAULT NULL,
  `is_anniversary` tinyint(1) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `badge_icon` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_user_id_event_type_index` (`user_id`,`event_type`),
  KEY `events_event_date_is_anniversary_index` (`event_date`,`is_anniversary`),
  KEY `events_post_id_index` (`post_id`),
  CONSTRAINT `events_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES
(1,3,'event-ES4u4RFw9IZAMMhrPXrlodkh','engagement','awd',NULL,'2026-04-22',2026,0,0,'💍',NULL,'2026-04-21 23:21:40','2026-04-21 23:21:40',NULL,NULL);
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `follows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint(20) unsigned NOT NULL,
  `followed_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `follows_follower_id_followed_id_unique` (`follower_id`,`followed_id`),
  KEY `follows_followed_id_index` (`followed_id`),
  KEY `follows_follower_id_index` (`follower_id`),
  CONSTRAINT `follows_followed_id_foreign` FOREIGN KEY (`followed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `follows_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `follows`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `follows` WRITE;
/*!40000 ALTER TABLE `follows` DISABLE KEYS */;
INSERT INTO `follows` VALUES
(1,4,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(2,4,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(3,4,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(4,4,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(5,4,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(6,4,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(7,4,10,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(8,5,10,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(9,5,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(10,5,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(11,5,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(12,6,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(13,6,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(14,6,10,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(15,6,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(16,6,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(17,6,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(18,6,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(19,7,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(20,7,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(21,7,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(22,7,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(23,8,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(24,8,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(25,8,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(26,9,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(27,9,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(28,10,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(29,10,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(30,10,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(31,10,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(32,10,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(33,10,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(34,10,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(35,11,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(36,11,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(37,11,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(38,11,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(39,11,10,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(40,11,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(41,11,8,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(74,12,3,'2026-04-25 13:11:37','2026-04-25 13:11:37'),
(77,13,12,'2026-04-27 12:40:21','2026-04-27 12:40:21'),
(78,13,3,'2026-04-27 12:40:26','2026-04-27 12:40:26'),
(79,3,13,'2026-04-27 13:00:04','2026-04-27 13:00:04'),
(80,3,12,'2026-04-27 16:46:10','2026-04-27 16:46:10');
/*!40000 ALTER TABLE `follows` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_members_group_id_user_id_unique` (`group_id`,`user_id`),
  KEY `group_members_user_id_foreign` (`user_id`),
  CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_members`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
INSERT INTO `group_members` VALUES
(1,1,10,'member','2026-04-21 22:59:48'),
(2,1,11,'member','2026-04-21 22:59:48'),
(3,1,6,'member','2026-04-21 22:59:48'),
(4,2,9,'admin','2026-04-21 22:59:48'),
(5,2,10,'member','2026-04-21 22:59:48'),
(6,2,7,'member','2026-04-21 22:59:48'),
(7,2,5,'member','2026-04-21 22:59:48'),
(8,3,11,'member','2026-04-21 22:59:48'),
(9,3,9,'member','2026-04-21 22:59:48'),
(10,4,9,'admin','2026-04-21 22:59:48'),
(11,4,11,'member','2026-04-21 22:59:48'),
(12,4,6,'member','2026-04-21 22:59:48'),
(13,4,10,'member','2026-04-21 22:59:48'),
(14,4,8,'member','2026-04-21 22:59:48'),
(19,7,3,'admin','2026-04-27 12:10:08'),
(21,7,12,'member','2026-04-27 12:15:05'),
(23,7,13,'member','2026-04-27 13:01:06');
/*!40000 ALTER TABLE `group_members` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `creator_id` bigint(20) unsigned NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `slug` varchar(255) NOT NULL,
  `invite_link` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_slug_unique` (`slug`),
  UNIQUE KEY `groups_invite_link_unique` (`invite_link`),
  KEY `groups_creator_id_foreign` (`creator_id`),
  CONSTRAINT `groups_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES
(1,'Photography Enthusiasts','Share your best photos and tips! 📸',5,NULL,0,'BU9Jy7L3AZ59aHAgp9Lq','1nsQBluTztyT3qryVaqKCq0n','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(2,'Tech Talk','Discuss the latest in technology 💻',9,NULL,0,'hv9LDbkMWTMk1Q9dtPyQ','wY47uTvcBhwwdZltMHSYTO24','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(3,'Fitness Community','Stay motivated and healthy together 💪',4,NULL,0,'NLEd3t2gaWHZLcqaNgOv','0YVxYaxsVvCZHD3Uid7uzbyH','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(4,'Food Lovers','Share recipes and food experiences 🍕',9,NULL,0,'mw1ilwHUYo4UDELFDiin','SdGOlDKzinxi5tfFt3Firyg0','2026-04-21 22:59:48','2026-04-21 22:59:48'),
(7,'awd',NULL,3,NULL,0,'QJiyA16ZiyK7bQ6LIT3d','ofg41zz7FYS4cpZ4BXlQCgIn','2026-04-27 12:10:08','2026-04-27 12:10:08');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `hashtag_post`
--

DROP TABLE IF EXISTS `hashtag_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hashtag_post` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashtag_post_hashtag_id_post_id_unique` (`hashtag_id`,`post_id`),
  KEY `hashtag_post_post_id_foreign` (`post_id`),
  CONSTRAINT `hashtag_post_hashtag_id_foreign` FOREIGN KEY (`hashtag_id`) REFERENCES `hashtags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hashtag_post_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hashtag_post`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `hashtag_post` WRITE;
/*!40000 ALTER TABLE `hashtag_post` DISABLE KEYS */;
/*!40000 ALTER TABLE `hashtag_post` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `hashtags`
--

DROP TABLE IF EXISTS `hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashtags_name_unique` (`name`),
  UNIQUE KEY `hashtags_slug_unique` (`slug`),
  KEY `hashtags_name_index` (`name`),
  KEY `hashtags_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hashtags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `hashtags` WRITE;
/*!40000 ALTER TABLE `hashtags` DISABLE KEYS */;
/*!40000 ALTER TABLE `hashtags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `likes_user_id_post_id_unique` (`user_id`,`post_id`),
  KEY `likes_post_id_created_at_index` (`post_id`,`created_at`),
  KEY `likes_post_id_index` (`post_id`),
  KEY `likes_user_id_index` (`user_id`),
  CONSTRAINT `likes_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `likes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `likes` WRITE;
/*!40000 ALTER TABLE `likes` DISABLE KEYS */;
INSERT INTO `likes` VALUES
(1,10,1,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(2,9,1,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(3,6,1,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(4,8,1,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(5,8,2,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(6,7,2,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(7,8,3,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(8,11,3,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(9,11,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(10,6,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(11,7,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(12,9,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(13,5,4,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(14,8,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(15,10,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(16,6,5,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(17,4,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(18,9,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(19,8,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(20,7,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(21,6,6,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(22,9,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(23,11,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(24,4,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(25,10,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(26,7,7,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(27,8,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(28,11,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(29,6,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(30,10,9,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(31,7,10,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(32,11,11,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(33,9,12,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(34,4,13,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(35,5,13,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(36,9,13,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(37,11,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(38,5,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(39,9,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(40,4,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(41,8,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(42,5,15,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(43,9,15,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(44,4,15,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(45,11,15,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(46,9,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(47,11,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(48,4,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(49,8,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(50,6,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(51,8,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(52,9,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(53,4,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(54,6,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(55,10,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(56,6,19,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(57,9,19,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(58,6,20,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(59,10,20,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(60,5,20,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(61,7,21,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(62,7,22,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(63,6,22,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(64,4,22,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(65,10,22,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(66,5,22,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(67,9,23,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(68,4,23,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(69,10,23,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(70,4,24,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(71,9,24,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(72,4,25,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(73,10,27,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(74,7,27,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(75,11,27,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(76,4,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(77,6,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(78,8,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(79,10,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(80,5,28,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(81,7,29,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(82,5,29,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(83,11,29,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(84,5,34,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(85,4,34,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(86,9,34,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(87,4,35,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(88,9,35,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(89,11,35,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(90,8,35,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(91,9,37,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(92,5,37,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(93,7,37,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(94,10,37,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(95,4,38,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(96,10,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(97,4,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(98,6,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(99,7,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(100,5,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(103,3,41,'2026-04-21 23:01:11','2026-04-21 23:01:11'),
(112,12,41,'2026-04-22 10:33:37','2026-04-22 10:33:37');
/*!40000 ALTER TABLE `likes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mentions`
--

DROP TABLE IF EXISTS `mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mentioner_id` bigint(20) unsigned NOT NULL,
  `mentioned_id` bigint(20) unsigned NOT NULL,
  `mentionable_type` varchar(255) NOT NULL,
  `mentionable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mention` (`mentioner_id`,`mentioned_id`,`mentionable_type`,`mentionable_id`),
  KEY `mentions_mentioned_id_foreign` (`mentioned_id`),
  KEY `mentions_mentionable_type_mentionable_id_index` (`mentionable_type`,`mentionable_id`),
  KEY `mentions_mentioner_id_mentioned_id_index` (`mentioner_id`,`mentioned_id`),
  CONSTRAINT `mentions_mentioned_id_foreign` FOREIGN KEY (`mentioned_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mentions_mentioner_id_foreign` FOREIGN KEY (`mentioner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mentions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mentions` WRITE;
/*!40000 ALTER TABLE `mentions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mentions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `message_reactions`
--

DROP TABLE IF EXISTS `message_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  `reaction_type` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_reactions_user_id_message_id_unique` (`user_id`,`message_id`),
  KEY `message_reactions_message_id_reaction_type_index` (`message_id`,`reaction_type`),
  CONSTRAINT `message_reactions_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `message_reactions` WRITE;
/*!40000 ALTER TABLE `message_reactions` DISABLE KEYS */;
INSERT INTO `message_reactions` VALUES
(15,12,256,'❤️','2026-04-26 17:38:46','2026-04-26 17:38:46'),
(17,12,255,'😂','2026-04-26 17:38:54','2026-04-26 17:38:54'),
(18,3,255,'❤️','2026-04-26 18:35:37','2026-04-26 18:35:37'),
(19,3,256,'❤️','2026-04-26 18:36:01','2026-04-26 18:36:01'),
(24,12,271,'😂','2026-04-26 20:07:40','2026-04-26 20:07:40'),
(25,3,270,'😂','2026-04-26 20:08:40','2026-04-26 20:08:40'),
(29,12,270,'😂','2026-04-26 20:51:26','2026-04-26 20:51:26'),
(31,12,272,'❤️','2026-04-26 20:57:38','2026-04-26 20:57:38'),
(32,3,273,'😂','2026-04-26 21:02:05','2026-04-26 21:02:05'),
(34,3,288,'❤️','2026-04-27 09:42:46','2026-04-27 09:42:57'),
(36,3,289,'😂','2026-04-27 10:38:22','2026-04-27 10:38:22'),
(37,12,290,'❤️','2026-04-27 11:05:40','2026-04-27 11:05:40'),
(38,3,291,'😂','2026-04-27 11:06:10','2026-04-27 11:06:10'),
(39,12,293,'😂','2026-04-27 11:13:51','2026-04-27 11:13:51'),
(40,3,298,'❤️','2026-04-27 12:04:54','2026-04-27 12:04:54'),
(41,12,299,'❤️','2026-04-27 12:04:57','2026-04-27 12:04:57'),
(42,12,317,'❤️','2026-04-27 12:38:05','2026-04-27 12:38:05'),
(43,12,314,'😂','2026-04-27 12:57:25','2026-04-27 12:57:25'),
(44,12,333,'❤️','2026-04-27 13:24:10','2026-04-27 13:24:10'),
(45,3,333,'❤️','2026-04-27 13:24:14','2026-04-27 13:24:14'),
(46,13,333,'❤️','2026-04-27 13:24:23','2026-04-27 13:24:23'),
(47,12,338,'❤️','2026-04-27 16:26:46','2026-04-27 16:26:46');
/*!40000 ALTER TABLE `message_reactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `message_receipts`
--

DROP TABLE IF EXISTS `message_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_receipts_message_id_user_id_unique` (`message_id`,`user_id`),
  KEY `message_receipts_user_id_foreign` (`user_id`),
  CONSTRAINT `message_receipts_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_receipts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_receipts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `message_receipts` WRITE;
/*!40000 ALTER TABLE `message_receipts` DISABLE KEYS */;
INSERT INTO `message_receipts` VALUES
(1,306,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(2,308,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(3,309,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(4,310,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(5,313,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(6,319,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(7,326,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(8,331,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(9,333,3,'2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35','2026-04-27 13:56:35'),
(10,334,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(11,301,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(12,305,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(13,306,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(14,307,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(15,308,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(16,309,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(17,310,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(18,311,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(19,312,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(20,313,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(21,314,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(22,317,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(23,319,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(24,325,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(25,327,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(26,328,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(27,329,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(28,330,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(29,331,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(30,332,13,'2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(31,301,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(32,305,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(33,307,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(34,311,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(35,312,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(36,314,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(37,317,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(38,325,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(39,326,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(40,327,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(41,328,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(42,329,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(43,330,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(44,332,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(45,333,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(46,334,12,'2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42','2026-04-27 15:57:42'),
(47,335,13,'2026-04-27 15:58:36','2026-04-27 15:58:36','2026-04-27 15:58:36','2026-04-27 15:58:36'),
(48,335,12,'2026-04-27 15:58:49','2026-04-27 15:58:49','2026-04-27 15:58:49','2026-04-27 15:58:49'),
(49,336,3,'2026-04-27 15:59:03','2026-04-27 15:59:03','2026-04-27 15:59:03','2026-04-27 15:59:03'),
(50,336,13,'2026-04-27 15:59:03','2026-04-27 15:59:03','2026-04-27 15:59:03','2026-04-27 15:59:03'),
(51,337,3,'2026-04-27 16:09:21','2026-04-27 16:09:21','2026-04-27 16:09:08','2026-04-27 16:09:21'),
(52,337,13,'2026-04-27 16:09:14','2026-04-27 16:09:14','2026-04-27 16:09:08','2026-04-27 16:09:14'),
(53,338,3,'2026-04-27 16:26:37','2026-04-27 16:26:37','2026-04-27 16:26:37','2026-04-27 16:26:37'),
(54,338,12,'2026-04-27 16:26:37','2026-04-27 16:26:37','2026-04-27 16:26:37','2026-04-27 16:26:37'),
(55,339,13,'2026-04-27 16:27:15','2026-04-27 16:27:15','2026-04-27 16:27:14','2026-04-27 16:27:15'),
(56,339,12,'2026-04-27 16:30:55','2026-04-27 16:30:55','2026-04-27 16:27:15','2026-04-27 16:30:55'),
(57,340,3,'2026-04-27 16:34:23','2026-04-27 16:34:23','2026-04-27 16:33:41','2026-04-27 16:34:23'),
(58,340,12,'2026-04-27 16:45:36','2026-04-27 16:45:36','2026-04-27 16:33:41','2026-04-27 16:45:36'),
(59,341,3,'2026-04-27 16:34:23','2026-04-27 16:34:23','2026-04-27 16:33:55','2026-04-27 16:34:23'),
(60,341,12,'2026-04-27 16:45:36','2026-04-27 16:45:36','2026-04-27 16:33:55','2026-04-27 16:45:36'),
(61,342,3,'2026-04-27 16:34:23','2026-04-27 16:34:23','2026-04-27 16:34:05','2026-04-27 16:34:23'),
(62,342,12,'2026-04-27 16:45:36','2026-04-27 16:45:36','2026-04-27 16:34:05','2026-04-27 16:45:36'),
(63,343,3,'2026-04-27 16:34:23','2026-04-27 16:34:23','2026-04-27 16:34:09','2026-04-27 16:34:23'),
(64,343,12,'2026-04-27 16:34:09',NULL,'2026-04-27 16:34:09','2026-04-27 16:34:09'),
(65,344,3,'2026-04-27 16:41:14','2026-04-27 16:41:14','2026-04-27 16:41:14','2026-04-27 16:41:14');
/*!40000 ALTER TABLE `message_receipts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `visible_to` bigint(20) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `type` enum('text','image','video','audio','document','gif','sticker','story_reply','group_invite','voice','system') DEFAULT 'text',
  `duration` int(11) DEFAULT NULL,
  `waveform_peaks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`waveform_peaks`)),
  `media_path` longtext DEFAULT NULL,
  `media_thumbnail` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `media_size` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_for` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deleted_for`)),
  `deleted_by_sender` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_created_at_index` (`conversation_id`,`created_at`),
  KEY `messages_sender_id_is_read_index` (`sender_id`,`is_read`),
  KEY `messages_sender_id_created_at_index` (`sender_id`,`created_at`),
  KEY `messages_read_at_index` (`read_at`),
  KEY `messages_notified_at_index` (`notified_at`),
  KEY `messages_conversation_id_created_at_deleted_at_index` (`conversation_id`,`created_at`,`deleted_at`),
  KEY `messages_deleted_for_index` (`deleted_for`(768)),
  KEY `messages_visible_to_index` (`visible_to`),
  KEY `messages_deleted_by_sender_index` (`deleted_by_sender`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_visible_to_foreign` FOREIGN KEY (`visible_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=345 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES
(3,2,4,NULL,'Hey! How are you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(4,2,5,NULL,'I\'m good! How about you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(5,2,4,NULL,'Doing great, thanks for asking!','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(6,2,5,NULL,'That\'s awesome! 😊','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(7,3,5,NULL,'Hey! How are you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(8,3,6,NULL,'I\'m good! How about you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(9,3,5,NULL,'Doing great, thanks for asking!','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:48','2026-04-21 22:59:48',NULL,NULL,0),
(10,3,6,NULL,'That\'s awesome! 😊','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49',NULL,NULL,0),
(11,4,6,NULL,'Hey! How are you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49',NULL,NULL,0),
(12,4,7,NULL,'I\'m good! How about you?','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49',NULL,NULL,0),
(13,4,6,NULL,'Doing great, thanks for asking!','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49',NULL,NULL,0),
(14,4,7,NULL,'That\'s awesome! 😊','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2026-04-21 22:59:49','2026-04-21 22:59:49',NULL,NULL,0),
(254,13,3,NULL,'system_cleared','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 17:38:35','2026-04-26 17:38:13',NULL,'2026-04-26 17:35:44','2026-04-26 17:38:35',NULL,NULL,0),
(255,13,3,NULL,'as','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 17:38:35','2026-04-26 17:38:33',NULL,'2026-04-26 17:38:33','2026-04-26 17:38:35',NULL,NULL,0),
(256,13,12,NULL,'hahahahahha','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 17:38:41','2026-04-26 17:38:40',NULL,'2026-04-26 17:38:40','2026-04-26 17:38:41',NULL,NULL,0),
(257,13,12,NULL,'jsjs','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 18:58:56','2026-04-26 18:58:56',NULL,'2026-04-26 18:58:56','2026-04-26 18:58:56',NULL,NULL,0),
(258,13,12,NULL,'hjjjijo','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 18:59:18','2026-04-26 18:59:18',NULL,'2026-04-26 18:59:18','2026-04-26 18:59:18',NULL,NULL,0),
(259,13,12,NULL,'jjijk','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:00:07','2026-04-26 19:00:06',NULL,'2026-04-26 19:00:06','2026-04-26 19:00:07',NULL,NULL,0),
(260,13,3,NULL,'wdqwd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:00:21','2026-04-26 19:00:17',NULL,'2026-04-26 19:00:17','2026-04-26 19:00:21',NULL,NULL,0),
(261,13,12,NULL,'udkw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:00:25','2026-04-26 19:00:25',NULL,'2026-04-26 19:00:25','2026-04-26 19:00:25',NULL,NULL,0),
(262,13,3,NULL,'fe','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:04:28','2026-04-26 19:01:29',NULL,'2026-04-26 19:01:29','2026-04-26 19:04:28',NULL,NULL,0),
(263,13,12,NULL,'wije','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:04:30','2026-04-26 19:04:30',NULL,'2026-04-26 19:04:30','2026-04-26 19:04:30',NULL,NULL,0),
(264,13,3,NULL,'asd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:07:13','2026-04-26 19:04:39',NULL,'2026-04-26 19:04:39','2026-04-26 19:07:13',NULL,NULL,0),
(265,13,3,NULL,'ad','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:07:13','2026-04-26 19:04:44',NULL,'2026-04-26 19:04:44','2026-04-26 19:07:13',NULL,NULL,0),
(266,13,12,NULL,'jsjs','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:07:17','2026-04-26 19:07:16',NULL,'2026-04-26 19:07:16','2026-04-26 19:07:17',NULL,NULL,0),
(267,13,12,NULL,'jsisjwiw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:08:44','2026-04-26 19:08:44',NULL,'2026-04-26 19:08:44','2026-04-26 19:08:44',NULL,NULL,0),
(268,13,12,NULL,'iwkwiw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:09:05','2026-04-26 19:08:56',NULL,'2026-04-26 19:08:56','2026-04-26 19:09:05',NULL,NULL,0),
(269,13,12,NULL,'dnawjkdnjk','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:15:24','2026-04-26 19:15:24',NULL,'2026-04-26 19:15:24','2026-04-26 19:15:24',NULL,NULL,0),
(270,13,3,NULL,'asldjwilk','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:15:28','2026-04-26 19:15:28',NULL,'2026-04-26 19:15:28','2026-04-26 19:15:28',NULL,NULL,0),
(271,13,12,NULL,'adjidlk','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 19:15:41','2026-04-26 19:15:38',NULL,'2026-04-26 19:15:38','2026-04-26 19:15:41',NULL,NULL,0),
(272,13,3,NULL,'dwad','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 20:56:35','2026-04-26 20:56:34',NULL,'2026-04-26 20:56:34','2026-04-26 20:56:35',NULL,NULL,0),
(273,13,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 20:56:49','2026-04-26 20:56:49',NULL,'2026-04-26 20:56:49','2026-04-26 20:56:49',NULL,NULL,0),
(274,13,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:02:18','2026-04-26 21:01:58',NULL,'2026-04-26 21:01:58','2026-04-26 21:02:18',NULL,NULL,0),
(275,13,3,NULL,'adi','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:08:14','2026-04-26 21:08:08',NULL,'2026-04-26 21:08:08','2026-04-26 21:08:14',NULL,NULL,0),
(276,13,3,NULL,'wda','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:12:14','2026-04-26 21:12:11',NULL,'2026-04-26 21:12:11','2026-04-26 21:12:14',NULL,NULL,0),
(277,13,3,NULL,'daw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:12:27','2026-04-26 21:12:24',NULL,'2026-04-26 21:12:24','2026-04-26 21:12:27',NULL,NULL,0),
(278,13,3,NULL,'sad','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:14:00','2026-04-26 21:13:57',NULL,'2026-04-26 21:13:56','2026-04-26 21:14:00',NULL,NULL,0),
(279,13,12,NULL,'adw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:32:27','2026-04-26 21:32:27',NULL,'2026-04-26 21:32:27','2026-04-26 21:32:27',NULL,NULL,0),
(280,13,12,NULL,'asd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:35:18','2026-04-26 21:35:18',NULL,'2026-04-26 21:35:18','2026-04-26 21:35:18',NULL,NULL,0),
(281,13,12,NULL,'asd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-26 21:43:04','2026-04-26 21:43:04',NULL,'2026-04-26 21:43:04','2026-04-26 21:43:04',NULL,NULL,0),
(282,13,12,NULL,'نصهث','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:21:12','2026-04-27 09:21:12',NULL,'2026-04-27 09:21:12','2026-04-27 09:21:12',NULL,NULL,0),
(283,13,12,NULL,'يهني','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:29:27','2026-04-27 09:29:27',NULL,'2026-04-27 09:29:27','2026-04-27 09:29:27',NULL,NULL,0),
(284,13,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:30:09','2026-04-27 09:30:08',NULL,'2026-04-27 09:30:08','2026-04-27 09:30:09',NULL,NULL,0),
(285,13,12,NULL,'يننس','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:35:22','2026-04-27 09:35:22',NULL,'2026-04-27 09:35:22','2026-04-27 09:35:22',NULL,NULL,0),
(286,13,12,NULL,'ءمس','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:37:47','2026-04-27 09:37:46',NULL,'2026-04-27 09:37:46','2026-04-27 09:37:47',NULL,NULL,0),
(287,13,12,NULL,'هسنص','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:42:20','2026-04-27 09:40:30',NULL,'2026-04-27 09:40:30','2026-04-27 09:42:20',NULL,NULL,0),
(288,13,12,NULL,'نبي','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:42:20','2026-04-27 09:42:10',NULL,'2026-04-27 09:42:10','2026-04-27 09:42:20',NULL,NULL,0),
(289,13,12,NULL,'خسمص','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 09:43:55','2026-04-27 09:43:54',NULL,'2026-04-27 09:43:54','2026-04-27 09:43:55',NULL,NULL,0),
(290,13,3,NULL,'fjiwjdij','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 11:05:36','2026-04-27 11:05:31',NULL,'2026-04-27 11:05:31','2026-04-27 11:05:36',NULL,NULL,0),
(291,13,12,NULL,'flkma','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 11:06:00','2026-04-27 11:06:00',NULL,'2026-04-27 11:06:00','2026-04-27 11:06:00',NULL,NULL,0),
(292,13,12,NULL,'{\"__nexus_reply__\":true,\"reply_to\":{\"id\":289,\"user\":\"admin2\",\"content\":\"\\u062e\\u0633\\u0645\\u0635\",\"type\":\"text\"},\"content\":\"\\u0627\\u0627\"}','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 11:13:21','2026-04-27 11:13:20',NULL,'2026-04-27 11:13:20','2026-04-27 11:13:21',NULL,NULL,0),
(293,13,3,NULL,'{\"__nexus_reply__\":true,\"reply_to\":{\"id\":292,\"user\":\"admin2\",\"content\":\"\\u0627\\u0627\",\"type\":\"text\"},\"content\":\"fae\"}','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 11:13:43','2026-04-27 11:13:40',NULL,'2026-04-27 11:13:40','2026-04-27 11:13:43',NULL,NULL,0),
(294,13,12,NULL,'awdawd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:26','2026-04-27 12:03:45',NULL,'2026-04-27 12:03:45','2026-04-27 12:04:26',NULL,NULL,0),
(295,13,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:26','2026-04-27 12:03:56',NULL,'2026-04-27 12:03:56','2026-04-27 12:04:26',NULL,NULL,0),
(296,13,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:30','2026-04-27 12:04:29',NULL,'2026-04-27 12:04:29','2026-04-27 12:04:30',NULL,NULL,0),
(297,13,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:47','2026-04-27 12:04:37',NULL,'2026-04-27 12:04:37','2026-04-27 12:04:47',NULL,NULL,0),
(298,13,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:47','2026-04-27 12:04:41',NULL,'2026-04-27 12:04:41','2026-04-27 12:04:47',NULL,NULL,0),
(299,13,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:04:51','2026-04-27 12:04:51',NULL,'2026-04-27 12:04:51','2026-04-27 12:04:51',NULL,NULL,0),
(301,16,3,NULL,'admin created this group','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:37','2026-04-27 12:14:37',NULL,'2026-04-27 12:10:08','2026-04-27 12:14:37',NULL,NULL,0),
(302,13,3,NULL,'','group_invite',NULL,NULL,'{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\"}',NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:05','2026-04-27 12:10:20',NULL,'2026-04-27 12:10:20','2026-04-27 12:14:05',NULL,NULL,0),
(303,13,3,NULL,'','group_invite',NULL,NULL,'{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\"}',NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:23','2026-04-27 12:14:20',NULL,'2026-04-27 12:14:19','2026-04-27 12:14:23',NULL,NULL,0),
(304,13,3,NULL,'','group_invite',NULL,NULL,'{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\"}',NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:28','2026-04-27 12:14:28',NULL,'2026-04-27 12:14:27','2026-04-27 12:14:28',NULL,NULL,0),
(305,16,3,NULL,'admin2 added to the group by admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:37','2026-04-27 12:14:37',NULL,'2026-04-27 12:14:36','2026-04-27 12:14:37',NULL,NULL,0),
(306,16,12,NULL,'admin2 exited group','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:14:57','2026-04-27 12:14:57',NULL,'2026-04-27 12:14:57','2026-04-27 12:14:57',NULL,NULL,0),
(307,16,3,NULL,'admin2 added to the group by admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:15:06','2026-04-27 12:15:06',NULL,'2026-04-27 12:15:05','2026-04-27 12:15:06',NULL,NULL,0),
(308,16,12,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:26:58','2026-04-27 12:21:29',NULL,'2026-04-27 12:21:29','2026-04-27 12:26:58',NULL,NULL,0),
(309,16,12,NULL,'adwadw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:26:58','2026-04-27 12:26:55',NULL,'2026-04-27 12:26:55','2026-04-27 12:26:58',NULL,NULL,0),
(310,16,12,NULL,'adwaw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:27:08','2026-04-27 12:27:08',NULL,'2026-04-27 12:27:08','2026-04-27 12:27:08',NULL,NULL,0),
(311,16,3,NULL,'awwda','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:27:12','2026-04-27 12:27:11',NULL,'2026-04-27 12:27:11','2026-04-27 12:27:12',NULL,NULL,0),
(312,16,3,NULL,'dawd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:27:27','2026-04-27 12:27:27',NULL,'2026-04-27 12:27:27','2026-04-27 12:27:27',NULL,NULL,0),
(313,16,12,NULL,'{\"__nexus_reply__\":true,\"reply_to\":{\"id\":312,\"user\":\"admin\",\"content\":\"dawd\",\"type\":\"text\"},\"content\":\"adwad\"}','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:27:33','2026-04-27 12:27:33',NULL,'2026-04-27 12:27:33','2026-04-27 12:27:33',NULL,NULL,0),
(314,16,3,NULL,'awdadw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:27:54','2026-04-27 12:27:50',NULL,'2026-04-27 12:27:50','2026-04-27 12:27:54',NULL,NULL,0),
(315,13,12,NULL,'awdawd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:37:05','2026-04-27 12:29:24',NULL,'2026-04-27 12:29:24','2026-04-27 12:37:05',NULL,NULL,0),
(316,13,3,NULL,'aawdaw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:37:44','2026-04-27 12:37:08',NULL,'2026-04-27 12:37:08','2026-04-27 12:37:44',NULL,NULL,0),
(317,16,3,NULL,'adwj','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:37:54','2026-04-27 12:37:15',NULL,'2026-04-27 12:37:15','2026-04-27 12:37:54',NULL,NULL,0),
(318,13,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:37:44','2026-04-27 12:37:22',NULL,'2026-04-27 12:37:22','2026-04-27 12:37:44',NULL,NULL,0),
(319,16,12,NULL,'adwd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:37:57','2026-04-27 12:37:57',NULL,'2026-04-27 12:37:57','2026-04-27 12:37:57',NULL,NULL,0),
(321,18,13,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:47:39','2026-04-27 12:47:34',NULL,'2026-04-27 12:47:34','2026-04-27 12:47:39',NULL,NULL,0),
(322,18,12,NULL,'lse','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 12:47:43','2026-04-27 12:47:43',NULL,'2026-04-27 12:47:43','2026-04-27 12:47:43',NULL,NULL,0),
(323,19,13,NULL,'asd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:22:40','2026-04-27 12:48:01',NULL,'2026-04-27 12:48:01','2026-04-27 13:22:40',NULL,NULL,0),
(324,19,3,NULL,'','group_invite',NULL,NULL,'{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\"}',NULL,NULL,NULL,NULL,0,'2026-04-27 13:00:18','2026-04-27 13:00:18',NULL,'2026-04-27 13:00:18','2026-04-27 13:00:18',NULL,NULL,0),
(325,16,3,NULL,'admin3 added to the group by admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:00:25','2026-04-27 13:00:25',NULL,'2026-04-27 13:00:24','2026-04-27 13:00:25',NULL,NULL,0),
(326,16,13,NULL,'admin3 exited group','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:01:10','2026-04-27 13:00:59',NULL,'2026-04-27 13:00:59','2026-04-27 13:01:10',NULL,NULL,0),
(327,16,3,NULL,'admin3 added to the group by admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:01:07','2026-04-27 13:01:07',NULL,'2026-04-27 13:01:06','2026-04-27 13:01:07',NULL,NULL,0),
(328,16,3,NULL,'admin made admin3 an admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:21:55','2026-04-27 13:21:55',NULL,'2026-04-27 13:21:54','2026-04-27 13:21:55',NULL,NULL,0),
(329,16,3,13,'You are admin now','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:21:55','2026-04-27 13:21:55',NULL,'2026-04-27 13:21:54','2026-04-27 13:21:55',NULL,NULL,0),
(330,16,3,NULL,'admin removed admin3 from admin','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:22:05','2026-04-27 13:22:05',NULL,'2026-04-27 13:22:04','2026-04-27 13:22:05',NULL,NULL,0),
(331,16,12,NULL,'65t','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:23:18','2026-04-27 13:23:17',NULL,'2026-04-27 13:23:17','2026-04-27 13:23:18',NULL,NULL,0),
(332,16,3,NULL,'saws','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:23:57','2026-04-27 13:23:57',NULL,'2026-04-27 13:23:57','2026-04-27 13:23:57',NULL,NULL,0),
(333,16,13,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 13:24:03','2026-04-27 13:24:02',NULL,'2026-04-27 13:24:02','2026-04-27 13:24:03',NULL,NULL,0),
(334,16,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-27 13:56:49',NULL,'2026-04-27 13:56:49','2026-04-27 13:56:49',NULL,NULL,0),
(335,16,3,NULL,'awd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-27 15:58:36',NULL,'2026-04-27 15:58:36','2026-04-27 15:58:36',NULL,NULL,0),
(336,16,12,NULL,'نلخ','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-27 15:59:02',NULL,'2026-04-27 15:59:02','2026-04-27 15:59:02',NULL,NULL,0),
(337,16,12,NULL,'زؤنينينميمثث','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 16:09:14','2026-04-27 16:09:07',NULL,'2026-04-27 16:09:07','2026-04-27 16:09:14',NULL,NULL,0),
(338,16,13,NULL,'','image',NULL,NULL,'[{\"type\":\"image\",\"path\":\"chat\\/media\\/Pn4TfEz8P3mseLgu4x3tHHRLg0JodcnElMt2EyO8.png\",\"original_filename\":\"Screenshot_2026-04-23_20_24_45.png\",\"size\":43776}]',NULL,NULL,NULL,NULL,0,'2026-04-27 16:26:37','2026-04-27 16:26:37',NULL,'2026-04-27 16:26:36','2026-04-27 16:26:37',NULL,NULL,0),
(339,16,3,NULL,'{\"__nexus_reply__\":true,\"reply_to\":{\"id\":338,\"user\":\"admin3\",\"content\":\"[Image]\",\"type\":\"image\"},\"content\":\"hhh\"}','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 16:30:55','2026-04-27 16:27:15',NULL,'2026-04-27 16:27:14','2026-04-27 16:30:55',NULL,NULL,0),
(340,16,13,NULL,'awdwd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 16:45:36','2026-04-27 16:33:41',NULL,'2026-04-27 16:33:40','2026-04-27 16:45:36',NULL,NULL,0),
(341,16,13,NULL,'awdawd','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 16:45:36','2026-04-27 16:33:55',NULL,'2026-04-27 16:33:55','2026-04-27 16:45:36',NULL,NULL,0),
(342,16,13,NULL,'adw','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-04-27 16:45:36','2026-04-27 16:34:05',NULL,'2026-04-27 16:34:05','2026-04-27 16:45:36',NULL,NULL,0),
(343,16,13,NULL,'12e','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-27 16:34:09',NULL,'2026-04-27 16:34:09','2026-04-27 16:34:38','2026-04-27 16:34:38',NULL,1),
(344,16,13,NULL,'','image',NULL,NULL,'[{\"type\":\"image\",\"path\":\"chat\\/media\\/wOQfb16RGHClNbV1jEWFGEOjFEQjk21m5EXrgyhc.png\",\"original_filename\":\"Screenshot_2026-04-24_02_56_35.png\",\"size\":71571},{\"type\":\"image\",\"path\":\"chat\\/media\\/cdwyRLd2rzCOtHVOQFeVxyM8rZow3Pf3kmpkjnwg.png\",\"original_filename\":\"Screenshot_2026-04-24_12_42_47.png\",\"size\":17155},{\"type\":\"image\",\"path\":\"chat\\/media\\/3Lm3MuF9xDsEZkUSqQ16ZOwGtJZEtUIW6hrT2rMd.png\",\"original_filename\":\"Screenshot_2026-04-24_12_47_06.png\",\"size\":262312},{\"type\":\"image\",\"path\":\"chat\\/media\\/83H6HbXyuVYYfGpJIqjTmCGuXeBO9HJTLIRHMSju.png\",\"original_filename\":\"Screenshot_2026-04-24_13_13_28.png\",\"size\":193997},{\"type\":\"image\",\"path\":\"chat\\/media\\/dgzOIWc9Xq72fra3ATOTy6Q91Hefrr7gwZwnQLP3.png\",\"original_filename\":\"Screenshot_2026-04-24_13_52_45.png\",\"size\":8289},{\"type\":\"image\",\"path\":\"chat\\/media\\/v7X2bSku5cRpaNJc2uSvnVF3bP9Z0AHlDTmTCEqy.png\",\"original_filename\":\"Screenshot_2026-04-24_13_54_21.png\",\"size\":11400},{\"type\":\"image\",\"path\":\"chat\\/media\\/LABsYNtGI6e0U9Q73W0IIJmMZrQxfCbCuDBGuDWz.png\",\"original_filename\":\"Screenshot_2026-04-24_13_58_28.png\",\"size\":42215},{\"type\":\"image\",\"path\":\"chat\\/media\\/gFWBRHuxQBq5b2P1AzGaSr3M0cZsdFCWu6HJ5PBp.png\",\"original_filename\":\"Screenshot_2026-04-24_15_52_51.png\",\"size\":7287},{\"type\":\"image\",\"path\":\"chat\\/media\\/8clraTDlnrnyFSBCoVeZN83mmDAxfv9a1HLD7w8C.png\",\"original_filename\":\"Screenshot_2026-04-24_20_04_27.png\",\"size\":15239},{\"type\":\"image\",\"path\":\"chat\\/media\\/0Ish1TPWBm9fxYa7PZV5XT1pdm2KXIcUTWSIQivn.png\",\"original_filename\":\"Screenshot_2026-04-24_20_20_19.png\",\"size\":55814},{\"type\":\"image\",\"path\":\"chat\\/media\\/qTlGZQtObGb9UWxmrsqCCry3cT0voQNmxrSP68CG.png\",\"original_filename\":\"Screenshot_2026-04-24_20_51_14.png\",\"size\":5867},{\"type\":\"image\",\"path\":\"chat\\/media\\/jOaeyoA7RYMzGC8rRwp2S5oJJpQppCmyOkCaI7ea.png\",\"original_filename\":\"Screenshot_2026-04-24_21_01_46.png\",\"size\":23543},{\"type\":\"image\",\"path\":\"chat\\/media\\/jCPySNt1LztNtL82aXujmwWbFmoL6rujlf8g9BK9.png\",\"original_filename\":\"Screenshot_2026-04-24_22_07_07.png\",\"size\":420062},{\"type\":\"image\",\"path\":\"chat\\/media\\/fHDl4KgV4oF6L8Mq0FOUZkjxYnstYCDAlkqfQDiP.png\",\"original_filename\":\"Screenshot_2026-04-24_22_42_18.png\",\"size\":2518},{\"type\":\"image\",\"path\":\"chat\\/media\\/04cOcKk7UMnW8IfdaXzUhQWwj6mHbi6im88iaNeM.png\",\"original_filename\":\"Screenshot_2026-04-24_23_10_29.png\",\"size\":14805},{\"type\":\"image\",\"path\":\"chat\\/media\\/Bw2jBjI8dOCQnDr0o35ToCLY7AXmkespbrwdArDU.png\",\"original_filename\":\"Screenshot_2026-04-24_23_13_31.png\",\"size\":36655},{\"type\":\"image\",\"path\":\"chat\\/media\\/2wXVbB4FHckkN8NomKw6ypteqhw4og2WyP3I15gw.png\",\"original_filename\":\"Screenshot_2026-04-24_23_36_48.png\",\"size\":35017},{\"type\":\"image\",\"path\":\"chat\\/media\\/QCjPq2ZNjVgxIqkVsQ6cLDsLVghM0SZlPc1bCzhl.png\",\"original_filename\":\"Screenshot_2026-04-24_23_51_04.png\",\"size\":13197}]',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-04-27 16:41:14','2026-04-27 16:41:46','2026-04-27 16:41:46',NULL,1);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_12_31_183416_create_posts_table',1),
(5,'2025_12_31_183428_create_follows_table',1),
(6,'2025_12_31_183440_create_likes_table',1),
(7,'2025_12_31_184455_create_comments_table',1),
(8,'2025_12_31_184509_create_comment_likes_table',1),
(9,'2025_12_31_185456_create_personal_access_tokens_table',1),
(10,'2025_12_31_190832_create_profiles_table',1),
(11,'2025_12_31_195043_add_is_private_to_profiles_table',1),
(12,'2025_12_31_195638_create_blocks_table',1),
(13,'2025_12_31_201829_add_media_to_posts_table',1),
(14,'2025_12_31_203558_add_is_private_to_posts_table',1),
(15,'2025_12_31_204120_create_post_media_table',1),
(16,'2025_12_31_204526_make_content_nullable_in_posts_table',1),
(17,'2025_12_31_211517_create_saved_posts_table',1),
(18,'2026_01_01_020301_create_stories_table',1),
(19,'2026_01_01_023011_add_views_to_stories_table',1),
(20,'2026_01_01_024005_create_story_views_table',1),
(21,'2026_01_01_024641_create_story_reactions_table',1),
(22,'2026_01_02_001005_add_full_name_to_profiles_table',1),
(23,'2026_01_02_020406_drop_full_name_from_profiles_table',1),
(24,'2026_01_02_045911_add_is_admin_to_users_table',1),
(25,'2026_01_02_052131_add_is_suspended_to_users_table',1),
(26,'2026_01_02_165014_create_conversations_table',1),
(27,'2026_01_02_165034_create_messages_table',1),
(28,'2026_01_02_171409_add_slug_to_conversations_table',1),
(29,'2026_01_02_180145_add_soft_deletes_to_messages_table',1),
(30,'2026_01_02_215252_create_notifications_table',1),
(31,'2026_01_03_214127_add_notified_at_to_messages_table',1),
(32,'2026_01_03_215758_add_indexes_for_performance',1),
(33,'2026_01_05_200731_create_mentions_table',1),
(34,'2026_01_16_123018_add_verification_code_to_users_table',2),
(35,'2026_01_19_140649_add_slug_to_posts_table',2),
(36,'2026_02_12_100000_add_username_to_users_table',2),
(37,'2026_02_13_091601_add_last_active_to_users',2),
(38,'2026_02_19_121800_add_media_to_messages_table',2),
(39,'2026_02_21_170301_create_groups_table',2),
(40,'2026_02_21_170303_create_group_members_table',2),
(41,'2026_02_21_170304_add_is_group_to_conversations_table',2),
(42,'2026_02_23_000000_add_system_type_to_messages',2),
(43,'2026_02_23_191845_add_group_invite_type_to_messages_table',2),
(44,'2026_02_26_013542_populate_usernames_for_existing_users',2),
(45,'2026_02_26_013853_add_username_changed_at_to_users_table',2),
(46,'2026_02_26_015139_update_existing_usernames_to_remove_hyphens',2),
(47,'2026_02_27_012712_increase_media_path_length_in_messages_table',2),
(48,'2026_02_28_021459_populate_story_slugs_and_add_unique_constraint',2),
(49,'2026_02_28_172610_add_visible_to_to_messages_table',2),
(50,'2026_03_02_000000_add_delivered_at_to_messages_table',2),
(51,'2026_03_02_000001_add_delete_options_to_messages_table',2),
(52,'2026_03_09_210144_add_inactive_reminder_fields_to_users_table',2),
(53,'2026_03_10_003407_make_password_column_nullable_in_users_table',2),
(54,'2026_03_10_232137_add_slug_and_invite_link_to_groups_table',2),
(55,'2026_03_10_232405_add_language_to_users_table',2),
(56,'2026_03_11_002925_add_performance_indexes',2),
(57,'2026_03_13_185834_make_user2_id_nullable_in_conversations_table',2),
(58,'2026_03_16_000000_add_messages_performance_indexes',2),
(59,'2026_03_16_054855_add_deleted_at_to_posts_table',2),
(60,'2026_03_17_000000_create_push_subscriptions_table',2),
(61,'2026_03_17_042304_fix_push_subscriptions_indexes',2),
(62,'2026_03_24_213851_create_post_reports_table',2),
(63,'2026_03_24_225702_add_admin_action_to_post_reports_table',2),
(64,'2026_03_24_230415_add_slug_to_post_reports_table',2),
(65,'2026_03_24_233722_create_hashtags_table',2),
(66,'2026_03_25_041923_create_activity_logs_table',2),
(67,'2026_03_25_052221_add_location_to_activity_logs_table',2),
(68,'2026_03_25_060244_add_more_location_fields_to_activity_logs_table',2),
(69,'2026_03_26_012822_make_user_id_nullable_in_activity_logs_table',2),
(70,'2026_03_26_032403_add_session_id_to_activity_logs_table',2),
(71,'2026_03_26_204544_add_voice_message_type_to_messages_table',2),
(72,'2026_03_27_000001_create_events_table',2),
(73,'2026_03_27_000002_create_event_reactions_table',2),
(74,'2026_03_27_025751_add_pinned_at_to_posts_table',2),
(75,'2026_03_27_033710_add_post_id_to_events_table',2),
(76,'2026_03_27_042419_add_soft_deletes_to_events_table',2),
(77,'2026_03_27_074019_make_media_fields_nullable_on_stories_table',2),
(78,'2026_03_27_075521_add_text_type_to_stories_media_type',2),
(79,'2026_03_27_081337_add_metadata_column_to_stories_table',2),
(80,'2026_03_29_123533_add_slug_to_events_table',2),
(81,'2026_04_22_141852_create_post_reactions_table',3),
(82,'2026_04_24_221800_create_conversation_mutes_table',4),
(83,'2026_04_25_100000_create_message_reactions_table',5),
(84,'2026_04_27_150600_add_system_type_to_messages_enum',6),
(85,'2026_04_27_163200_create_message_receipts_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  KEY `notifications_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=411 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,10,'follow','{\"follower_name\":\"admin\",\"follower_id\":3}',NULL,42,'App\\Models\\Follow','2026-04-21 23:01:32','2026-04-21 23:01:32'),
(2,10,'follow','{\"follower_name\":\"admin\",\"follower_id\":3}',NULL,43,'App\\Models\\Follow','2026-04-21 23:01:38','2026-04-21 23:01:38'),
(27,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,52,'App\\Models\\Follow','2026-04-22 10:40:52','2026-04-22 10:40:52'),
(28,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,53,'App\\Models\\Follow','2026-04-22 10:40:56','2026-04-22 10:40:56'),
(29,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,54,'App\\Models\\Follow','2026-04-22 10:40:59','2026-04-22 10:40:59'),
(30,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,55,'App\\Models\\Follow','2026-04-22 10:41:06','2026-04-22 10:41:06'),
(31,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,56,'App\\Models\\Follow','2026-04-22 10:43:39','2026-04-22 10:43:39'),
(32,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,57,'App\\Models\\Follow','2026-04-22 10:43:42','2026-04-22 10:43:42'),
(33,11,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,58,'App\\Models\\Follow','2026-04-22 10:44:39','2026-04-22 10:44:39'),
(34,11,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,59,'App\\Models\\Follow','2026-04-22 10:44:46','2026-04-22 10:44:46'),
(35,11,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,60,'App\\Models\\Follow','2026-04-22 10:44:46','2026-04-22 10:44:46'),
(36,11,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,61,'App\\Models\\Follow','2026-04-22 10:44:47','2026-04-22 10:44:47'),
(37,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,62,'App\\Models\\Follow','2026-04-22 10:46:02','2026-04-22 10:46:02'),
(38,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,63,'App\\Models\\Follow','2026-04-22 10:46:07','2026-04-22 10:46:07'),
(39,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,64,'App\\Models\\Follow','2026-04-22 10:46:11','2026-04-22 10:46:11'),
(40,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,65,'App\\Models\\Follow','2026-04-22 10:46:21','2026-04-22 10:46:21'),
(41,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,66,'App\\Models\\Follow','2026-04-22 10:46:25','2026-04-22 10:46:25'),
(42,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,67,'App\\Models\\Follow','2026-04-22 10:47:42','2026-04-22 10:47:42'),
(43,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,68,'App\\Models\\Follow','2026-04-22 10:47:45','2026-04-22 10:47:45'),
(44,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,69,'App\\Models\\Follow','2026-04-22 10:47:49','2026-04-22 10:47:49'),
(45,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,70,'App\\Models\\Follow','2026-04-22 10:47:51','2026-04-22 10:47:51'),
(46,10,'follow','{\"follower_name\":\"admin2\",\"follower_id\":12}',NULL,71,'App\\Models\\Follow','2026-04-22 10:47:55','2026-04-22 10:47:55'),
(68,4,'post_reaction','{\"reactor_username\":\"admin2\",\"reaction_type\":\"\\u2764\\ufe0f\",\"post_slug\":\"BdjIgkkirs9bsaGncHa9dZvi\"}',NULL,5,'App\\Models\\Post','2026-04-22 16:30:51','2026-04-22 16:30:51'),
(250,4,'post_reaction','{\"reactor_username\":\"admin\",\"reaction_type\":\"\\ud83d\\udc4d\",\"post_slug\":\"BdjIgkkirs9bsaGncHa9dZvi\"}',NULL,5,'App\\Models\\Post','2026-04-25 12:34:50','2026-04-25 12:34:50'),
(259,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"dawada\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:25',234,'App\\Models\\Message','2026-04-26 13:42:36','2026-04-26 21:06:25'),
(260,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"lkfalkn\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:17',235,'App\\Models\\Message','2026-04-26 13:46:24','2026-04-26 21:06:17'),
(261,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0627\\u062a\\u0646\\u0646\\u0627\\u0639\\u062e\\u062e\\u0647\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:17',236,'App\\Models\\Message','2026-04-26 13:48:17','2026-04-26 21:06:17'),
(262,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdawd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',237,'App\\Models\\Message','2026-04-26 13:54:57','2026-04-26 21:06:18'),
(263,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',238,'App\\Models\\Message','2026-04-26 13:57:12','2026-04-26 21:06:18'),
(264,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',239,'App\\Models\\Message','2026-04-26 13:59:00','2026-04-26 21:06:18'),
(265,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',240,'App\\Models\\Message','2026-04-26 14:01:16','2026-04-26 21:06:18'),
(266,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',241,'App\\Models\\Message','2026-04-26 14:05:17','2026-04-26 21:06:18'),
(267,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"aw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',242,'App\\Models\\Message','2026-04-26 14:05:58','2026-04-26 21:06:18'),
(268,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"aw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',243,'App\\Models\\Message','2026-04-26 14:06:19','2026-04-26 21:06:18'),
(269,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u064a\\u0646\\u0635\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',244,'App\\Models\\Message','2026-04-26 14:33:52','2026-04-26 21:06:18'),
(270,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u064a\\u062e\\u0645\\u062b\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',245,'App\\Models\\Message','2026-04-26 14:37:44','2026-04-26 21:06:18'),
(271,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u064a\\u0646\\u0646\\u064a\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',246,'App\\Models\\Message','2026-04-26 15:17:04','2026-04-26 21:06:18'),
(272,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:25',247,'App\\Models\\Message','2026-04-26 15:46:50','2026-04-26 21:06:25'),
(273,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u064a\\u0646\\u062b\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',248,'App\\Models\\Message','2026-04-26 15:47:30','2026-04-26 21:06:18'),
(274,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u064a\\u0646\\u062b\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',249,'App\\Models\\Message','2026-04-26 15:49:58','2026-04-26 21:06:18'),
(275,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u062e\\u064a\\u0645\\u062b\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',250,'App\\Models\\Message','2026-04-26 15:50:07','2026-04-26 21:06:18'),
(276,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"jrjr\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',251,'App\\Models\\Message','2026-04-26 16:07:04','2026-04-26 21:06:18'),
(277,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"ieke\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',252,'App\\Models\\Message','2026-04-26 16:32:55','2026-04-26 21:06:18'),
(278,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"dijsiejeiw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',253,'App\\Models\\Message','2026-04-26 17:19:17','2026-04-26 21:06:18'),
(279,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":253,\"conversation_id\":13}','2026-04-26 21:06:25',253,'App\\Models\\Message','2026-04-26 17:19:24','2026-04-26 21:06:25'),
(280,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude21\",\"message_id\":252,\"message_content\":\"ieke\",\"conversation_id\":13}','2026-04-26 21:06:26',252,'App\\Models\\Message','2026-04-26 17:31:09','2026-04-26 21:06:26'),
(281,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude22\",\"message_id\":252,\"message_content\":\"ieke\",\"conversation_id\":13}','2026-04-26 21:06:26',252,'App\\Models\\Message','2026-04-26 17:31:33','2026-04-26 21:06:26'),
(282,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":252,\"message_content\":\"ieke\",\"conversation_id\":13}','2026-04-26 21:06:26',252,'App\\Models\\Message','2026-04-26 17:31:35','2026-04-26 21:06:26'),
(283,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"as\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',255,'App\\Models\\Message','2026-04-26 17:38:33','2026-04-26 21:06:26'),
(284,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"hahahahahha\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',256,'App\\Models\\Message','2026-04-26 17:38:40','2026-04-26 21:06:18'),
(285,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":256,\"message_content\":\"hahahahahha\",\"conversation_id\":13}','2026-04-26 21:06:26',256,'App\\Models\\Message','2026-04-26 17:38:46','2026-04-26 21:06:26'),
(286,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":255,\"message_content\":\"as\",\"conversation_id\":13}','2026-04-26 21:06:18',255,'App\\Models\\Message','2026-04-26 17:38:54','2026-04-26 21:06:18'),
(287,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":256,\"message_content\":\"hahahahahha\",\"conversation_id\":13}','2026-04-26 21:06:26',256,'App\\Models\\Message','2026-04-26 17:39:00','2026-04-26 21:06:26'),
(288,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":256,\"message_content\":\"hahahahahha\",\"conversation_id\":13}','2026-04-26 21:06:26',256,'App\\Models\\Message','2026-04-26 18:36:01','2026-04-26 21:06:26'),
(289,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"jsjs\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',257,'App\\Models\\Message','2026-04-26 18:58:56','2026-04-26 21:06:18'),
(290,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"hjjjijo\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',258,'App\\Models\\Message','2026-04-26 18:59:18','2026-04-26 21:06:18'),
(291,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"jjijk\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',259,'App\\Models\\Message','2026-04-26 19:00:06','2026-04-26 21:06:18'),
(292,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"wdqwd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',260,'App\\Models\\Message','2026-04-26 19:00:17','2026-04-26 21:06:26'),
(293,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"udkw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',261,'App\\Models\\Message','2026-04-26 19:00:25','2026-04-26 21:06:18'),
(294,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"fe\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',262,'App\\Models\\Message','2026-04-26 19:01:29','2026-04-26 21:06:26'),
(295,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"wije\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',263,'App\\Models\\Message','2026-04-26 19:04:30','2026-04-26 21:06:18'),
(296,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"asd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',264,'App\\Models\\Message','2026-04-26 19:04:39','2026-04-26 21:06:26'),
(297,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"ad\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',265,'App\\Models\\Message','2026-04-26 19:04:44','2026-04-26 21:06:26'),
(298,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"jsjs\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',266,'App\\Models\\Message','2026-04-26 19:07:16','2026-04-26 21:06:18'),
(299,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"jsisjwiw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',267,'App\\Models\\Message','2026-04-26 19:08:44','2026-04-26 21:06:18'),
(300,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"iwkwiw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',268,'App\\Models\\Message','2026-04-26 19:08:56','2026-04-26 21:06:18'),
(301,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"dnawjkdnjk\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',269,'App\\Models\\Message','2026-04-26 19:15:24','2026-04-26 21:06:18'),
(302,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"asldjwilk\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',270,'App\\Models\\Message','2026-04-26 19:15:28','2026-04-26 21:06:26'),
(303,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adjidlk\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',271,'App\\Models\\Message','2026-04-26 19:15:38','2026-04-26 21:06:18'),
(304,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":270,\"message_content\":\"asldjwilk\",\"conversation_id\":13}','2026-04-26 21:06:18',270,'App\\Models\\Message','2026-04-26 19:16:22','2026-04-26 21:06:18'),
(305,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":271,\"message_content\":\"adjidlk\",\"conversation_id\":13}','2026-04-26 21:06:26',271,'App\\Models\\Message','2026-04-26 20:08:49','2026-04-26 21:06:26'),
(306,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":271,\"message_content\":\"adjidlk\",\"conversation_id\":13}','2026-04-26 21:06:26',271,'App\\Models\\Message','2026-04-26 20:11:25','2026-04-26 21:06:26'),
(307,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":271,\"message_content\":\"adjidlk\",\"conversation_id\":13}','2026-04-26 21:06:26',271,'App\\Models\\Message','2026-04-26 20:13:44','2026-04-26 21:06:26'),
(308,12,'comment_like','{\"liker_name\":\"admin\",\"liker_username\":\"admin\",\"liker_id\":3,\"comment_content\":\"\\u064a\\u062a\\u062a\\u062b\",\"post_slug\":\"7o0LeaZamWZdoQeSFgtKW1E4\",\"is_reply\":false}','2026-04-27 09:16:38',82,'App\\Models\\Comment','2026-04-26 20:16:47','2026-04-27 09:16:38'),
(309,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":270,\"message_content\":\"asldjwilk\",\"conversation_id\":13}','2026-04-26 21:06:18',270,'App\\Models\\Message','2026-04-26 20:51:26','2026-04-26 21:06:18'),
(310,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"dwad\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',272,'App\\Models\\Message','2026-04-26 20:56:34','2026-04-26 21:06:26'),
(311,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:18',273,'App\\Models\\Message','2026-04-26 20:56:49','2026-04-26 21:06:18'),
(312,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":272,\"message_content\":\"dwad\",\"conversation_id\":13}','2026-04-26 21:06:18',272,'App\\Models\\Message','2026-04-26 20:57:19','2026-04-26 21:06:18'),
(313,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":272,\"message_content\":\"dwad\",\"conversation_id\":13}','2026-04-26 21:06:18',272,'App\\Models\\Message','2026-04-26 20:57:38','2026-04-26 21:06:18'),
(314,12,'comment_like','{\"liker_name\":\"admin\",\"liker_username\":\"admin\",\"liker_id\":3,\"comment_content\":\"\\u064a\\u062a\\u062a\\u062b\",\"post_slug\":\"7o0LeaZamWZdoQeSFgtKW1E4\",\"is_reply\":false}','2026-04-27 09:16:38',82,'App\\Models\\Comment','2026-04-26 21:01:50','2026-04-27 09:16:38'),
(315,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:06:26',274,'App\\Models\\Message','2026-04-26 21:01:58','2026-04-26 21:06:26'),
(316,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":273,\"message_content\":\"awd\",\"conversation_id\":13}','2026-04-26 21:06:26',273,'App\\Models\\Message','2026-04-26 21:02:05','2026-04-26 21:06:26'),
(317,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adi\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:08:14',275,'App\\Models\\Message','2026-04-26 21:08:08','2026-04-26 21:08:14'),
(318,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"wda\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:12:14',276,'App\\Models\\Message','2026-04-26 21:12:11','2026-04-26 21:12:14'),
(319,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"daw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:12:27',277,'App\\Models\\Message','2026-04-26 21:12:24','2026-04-26 21:12:27'),
(320,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"sad\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:14:00',278,'App\\Models\\Message','2026-04-26 21:13:57','2026-04-26 21:14:00'),
(321,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":278,\"message_content\":\"sad\",\"conversation_id\":13}','2026-04-26 21:32:27',278,'App\\Models\\Message','2026-04-26 21:28:44','2026-04-26 21:32:27'),
(322,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:32:27',279,'App\\Models\\Message','2026-04-26 21:32:27','2026-04-26 21:32:27'),
(323,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"asd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:35:18',280,'App\\Models\\Message','2026-04-26 21:35:18','2026-04-26 21:35:18'),
(324,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"asd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-26 21:43:04',281,'App\\Models\\Message','2026-04-26 21:43:04','2026-04-26 21:43:04'),
(325,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u0635\\u0647\\u062b\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:21:12',282,'App\\Models\\Message','2026-04-27 09:21:12','2026-04-27 09:21:12'),
(326,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u064a\\u0647\\u0646\\u064a\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:29:27',283,'App\\Models\\Message','2026-04-27 09:29:27','2026-04-27 09:29:27'),
(327,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:30:09',284,'App\\Models\\Message','2026-04-27 09:30:08','2026-04-27 09:30:09'),
(328,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u064a\\u0646\\u0646\\u0633\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:35:22',285,'App\\Models\\Message','2026-04-27 09:35:22','2026-04-27 09:35:22'),
(329,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0621\\u0645\\u0633\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:37:47',286,'App\\Models\\Message','2026-04-27 09:37:46','2026-04-27 09:37:47'),
(330,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0647\\u0633\\u0646\\u0635\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:42:20',287,'App\\Models\\Message','2026-04-27 09:40:30','2026-04-27 09:42:20'),
(331,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u0628\\u064a\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:42:20',288,'App\\Models\\Message','2026-04-27 09:42:10','2026-04-27 09:42:20'),
(332,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":288,\"message_content\":\"\\u0646\\u0628\\u064a\",\"conversation_id\":13}','2026-04-27 09:43:48',288,'App\\Models\\Message','2026-04-27 09:42:46','2026-04-27 09:43:48'),
(333,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":288,\"message_content\":\"\\u0646\\u0628\\u064a\",\"conversation_id\":13}','2026-04-27 09:43:48',288,'App\\Models\\Message','2026-04-27 09:42:57','2026-04-27 09:43:48'),
(334,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u062e\\u0633\\u0645\\u0635\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 09:43:55',289,'App\\Models\\Message','2026-04-27 09:43:54','2026-04-27 09:43:55'),
(335,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":289,\"message_content\":\"\\u062e\\u0633\\u0645\\u0635\",\"conversation_id\":13}','2026-04-27 09:47:06',289,'App\\Models\\Message','2026-04-27 09:47:01','2026-04-27 09:47:06'),
(336,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude2e\",\"message_id\":289,\"message_content\":\"\\u062e\\u0633\\u0645\\u0635\",\"conversation_id\":13}','2026-04-27 10:19:01',289,'App\\Models\\Message','2026-04-27 09:47:09','2026-04-27 10:19:01'),
(337,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":289,\"message_content\":\"\\u062e\\u0633\\u0645\\u0635\",\"conversation_id\":13}','2026-04-27 10:19:01',289,'App\\Models\\Message','2026-04-27 09:47:14','2026-04-27 10:19:01'),
(338,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":289,\"message_content\":\"\\u062e\\u0633\\u0645\\u0635\",\"conversation_id\":13}','2026-04-27 10:38:44',289,'App\\Models\\Message','2026-04-27 10:38:22','2026-04-27 10:38:44'),
(339,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"fjiwjdij\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 11:05:36',290,'App\\Models\\Message','2026-04-27 11:05:31','2026-04-27 11:05:36'),
(340,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":290,\"message_content\":\"fjiwjdij\",\"conversation_id\":13}','2026-04-27 11:06:00',290,'App\\Models\\Message','2026-04-27 11:05:40','2026-04-27 11:06:00'),
(341,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"flkma\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 11:06:00',291,'App\\Models\\Message','2026-04-27 11:06:00','2026-04-27 11:06:00'),
(342,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":291,\"message_content\":\"flkma\",\"conversation_id\":13}','2026-04-27 11:12:06',291,'App\\Models\\Message','2026-04-27 11:06:10','2026-04-27 11:12:06'),
(343,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u21a9 \\u0627\\u0627\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 11:13:21',292,'App\\Models\\Message','2026-04-27 11:13:20','2026-04-27 11:13:21'),
(344,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u21a9 fae\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 11:13:43',293,'App\\Models\\Message','2026-04-27 11:13:40','2026-04-27 11:13:43'),
(345,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":293,\"message_content\":\"fae\",\"conversation_id\":13}','2026-04-27 11:13:52',293,'App\\Models\\Message','2026-04-27 11:13:51','2026-04-27 11:13:52'),
(346,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdawd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:26',294,'App\\Models\\Message','2026-04-27 12:03:45','2026-04-27 12:04:26'),
(347,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:26',295,'App\\Models\\Message','2026-04-27 12:03:56','2026-04-27 12:04:26'),
(348,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:30',296,'App\\Models\\Message','2026-04-27 12:04:29','2026-04-27 12:04:30'),
(349,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:47',297,'App\\Models\\Message','2026-04-27 12:04:37','2026-04-27 12:04:47'),
(350,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:47',298,'App\\Models\\Message','2026-04-27 12:04:41','2026-04-27 12:04:47'),
(351,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:04:51',299,'App\\Models\\Message','2026-04-27 12:04:51','2026-04-27 12:04:51'),
(352,12,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":298,\"message_content\":\"awd\",\"conversation_id\":13}','2026-04-27 12:14:05',298,'App\\Models\\Message','2026-04-27 12:04:54','2026-04-27 12:14:05'),
(353,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":299,\"message_content\":\"awd\",\"conversation_id\":13}','2026-04-27 12:04:58',299,'App\\Models\\Message','2026-04-27 12:04:57','2026-04-27 12:04:58'),
(354,12,'follow','{\"follower_name\":\"admin\",\"follower_id\":3}',NULL,75,'App\\Models\\Follow','2026-04-27 12:05:13','2026-04-27 12:05:13'),
(355,12,'group_invite','{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"inviter_id\":3,\"inviter_username\":\"admin\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\",\"conversation_id\":13}','2026-04-27 12:14:05',NULL,NULL,'2026-04-27 12:10:20','2026-04-27 12:14:05'),
(356,12,'group_invite','{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"inviter_id\":3,\"inviter_username\":\"admin\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\",\"conversation_id\":13}','2026-04-27 12:14:23',NULL,NULL,'2026-04-27 12:14:19','2026-04-27 12:14:23'),
(357,12,'group_invite','{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"inviter_id\":3,\"inviter_username\":\"admin\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\",\"conversation_id\":13}','2026-04-27 12:14:28',NULL,NULL,'2026-04-27 12:14:27','2026-04-27 12:14:28'),
(358,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:26:58',308,'App\\Models\\Message','2026-04-27 12:21:29','2026-04-27 12:26:58'),
(359,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adwadw\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:26:58',309,'App\\Models\\Message','2026-04-27 12:26:55','2026-04-27 12:26:58'),
(360,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adwaw\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:27:08',310,'App\\Models\\Message','2026-04-27 12:27:08','2026-04-27 12:27:08'),
(361,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awwda\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:27:12',311,'App\\Models\\Message','2026-04-27 12:27:11','2026-04-27 12:27:12'),
(362,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"dawd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:27:27',312,'App\\Models\\Message','2026-04-27 12:27:27','2026-04-27 12:27:27'),
(363,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u21a9 adwad\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:27:33',313,'App\\Models\\Message','2026-04-27 12:27:33','2026-04-27 12:27:33'),
(364,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdadw\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:27:54',314,'App\\Models\\Message','2026-04-27 12:27:50','2026-04-27 12:27:54'),
(365,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdawd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:37:05',315,'App\\Models\\Message','2026-04-27 12:29:24','2026-04-27 12:37:05'),
(366,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"aawdaw\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:37:44',316,'App\\Models\\Message','2026-04-27 12:37:08','2026-04-27 12:37:44'),
(367,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adwj\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:37:54',317,'App\\Models\\Message','2026-04-27 12:37:15','2026-04-27 12:37:54'),
(368,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":13}','2026-04-27 12:37:44',318,'App\\Models\\Message','2026-04-27 12:37:22','2026-04-27 12:37:44'),
(369,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adwd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 12:37:57',319,'App\\Models\\Message','2026-04-27 12:37:57','2026-04-27 12:37:57'),
(370,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":317,\"message_content\":\"adwj\",\"conversation_id\":16}','2026-04-27 12:38:06',317,'App\\Models\\Message','2026-04-27 12:38:05','2026-04-27 12:38:06'),
(371,12,'follow','{\"follower_name\":\"admin3\",\"follower_id\":13}',NULL,76,'App\\Models\\Follow','2026-04-27 12:40:11','2026-04-27 12:40:11'),
(372,12,'follow','{\"follower_name\":\"admin3\",\"follower_id\":13}',NULL,77,'App\\Models\\Follow','2026-04-27 12:40:21','2026-04-27 12:40:21'),
(373,3,'follow','{\"follower_name\":\"admin3\",\"follower_id\":13}','2026-04-27 16:50:59',78,'App\\Models\\Follow','2026-04-27 12:40:26','2026-04-27 16:50:59'),
(374,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdawd\",\"message_type\":\"text\",\"conversation_id\":17}',NULL,320,'App\\Models\\Message','2026-04-27 12:40:52','2026-04-27 12:40:52'),
(375,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":18}','2026-04-27 12:47:39',321,'App\\Models\\Message','2026-04-27 12:47:34','2026-04-27 12:47:39'),
(376,13,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"lse\",\"message_type\":\"text\",\"conversation_id\":18}','2026-04-27 12:47:43',322,'App\\Models\\Message','2026-04-27 12:47:43','2026-04-27 12:47:43'),
(377,3,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"asd\",\"message_type\":\"text\",\"conversation_id\":19}','2026-04-27 13:22:40',323,'App\\Models\\Message','2026-04-27 12:48:01','2026-04-27 13:22:40'),
(378,3,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\ud83d\\ude02\",\"message_id\":314,\"message_content\":\"awdadw\",\"conversation_id\":16}','2026-04-27 12:57:27',314,'App\\Models\\Message','2026-04-27 12:57:25','2026-04-27 12:57:27'),
(379,13,'follow','{\"follower_name\":\"admin\",\"follower_id\":3}',NULL,79,'App\\Models\\Follow','2026-04-27 13:00:04','2026-04-27 13:00:04'),
(380,13,'group_invite','{\"group_id\":7,\"group_name\":\"awd\",\"group_slug\":\"QJiyA16ZiyK7bQ6LIT3d\",\"inviter_id\":3,\"inviter_username\":\"admin\",\"invite_link\":\"ofg41zz7FYS4cpZ4BXlQCgIn\",\"conversation_id\":19}','2026-04-27 13:00:18',NULL,NULL,'2026-04-27 13:00:18','2026-04-27 13:00:18'),
(381,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"65t\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:23:30',331,'App\\Models\\Message','2026-04-27 13:23:17','2026-04-27 13:23:30'),
(382,13,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"65t\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:23:18',331,'App\\Models\\Message','2026-04-27 13:23:17','2026-04-27 13:23:18'),
(383,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"saws\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:23:58',332,'App\\Models\\Message','2026-04-27 13:23:57','2026-04-27 13:23:58'),
(384,13,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"saws\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:23:57',332,'App\\Models\\Message','2026-04-27 13:23:57','2026-04-27 13:23:57'),
(385,3,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:24:03',333,'App\\Models\\Message','2026-04-27 13:24:02','2026-04-27 13:24:03'),
(386,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:24:03',333,'App\\Models\\Message','2026-04-27 13:24:02','2026-04-27 13:24:03'),
(387,13,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":333,\"message_content\":\"awd\",\"conversation_id\":16}','2026-04-27 13:24:16',333,'App\\Models\\Message','2026-04-27 13:24:10','2026-04-27 13:24:16'),
(388,13,'chat_reaction','{\"reactor_name\":\"Admin\",\"reactor_username\":\"admin\",\"reactor_id\":3,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":333,\"message_content\":\"awd\",\"conversation_id\":16}','2026-04-27 13:24:16',333,'App\\Models\\Message','2026-04-27 13:24:14','2026-04-27 13:24:16'),
(389,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 15:57:42',334,'App\\Models\\Message','2026-04-27 13:56:49','2026-04-27 15:57:42'),
(390,13,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 13:56:49',334,'App\\Models\\Message','2026-04-27 13:56:49','2026-04-27 13:56:49'),
(391,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 15:58:49',335,'App\\Models\\Message','2026-04-27 15:58:36','2026-04-27 15:58:49'),
(392,13,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awd\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 15:58:36',335,'App\\Models\\Message','2026-04-27 15:58:36','2026-04-27 15:58:36'),
(393,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u0644\\u062e\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 15:59:03',336,'App\\Models\\Message','2026-04-27 15:59:02','2026-04-27 15:59:03'),
(394,13,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0646\\u0644\\u062e\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 15:59:03',336,'App\\Models\\Message','2026-04-27 15:59:02','2026-04-27 15:59:03'),
(395,3,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0632\\u0624\\u0646\\u064a\\u0646\\u064a\\u0646\\u0645\\u064a\\u0645\\u062b\\u062b\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 16:09:21',337,'App\\Models\\Message','2026-04-27 16:09:07','2026-04-27 16:09:21'),
(396,13,'message','{\"sender_name\":\"admin2\",\"sender_username\":\"admin2\",\"sender_id\":12,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u0632\\u0624\\u0646\\u064a\\u0646\\u064a\\u0646\\u0645\\u064a\\u0645\\u062b\\u062b\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 16:09:14',337,'App\\Models\\Message','2026-04-27 16:09:07','2026-04-27 16:09:14'),
(397,3,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"sent an image\",\"message_type\":\"image\",\"conversation_id\":16}','2026-04-27 16:26:37',338,'App\\Models\\Message','2026-04-27 16:26:36','2026-04-27 16:26:37'),
(398,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"sent an image\",\"message_type\":\"image\",\"conversation_id\":16}','2026-04-27 16:26:37',338,'App\\Models\\Message','2026-04-27 16:26:36','2026-04-27 16:26:37'),
(399,13,'chat_reaction','{\"reactor_name\":\"admin2\",\"reactor_username\":\"admin2\",\"reactor_id\":12,\"reactor_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"reaction_type\":\"\\u2764\\ufe0f\",\"message_id\":338,\"message_content\":\"[Image]\",\"conversation_id\":16}','2026-04-27 16:27:15',338,'App\\Models\\Message','2026-04-27 16:26:46','2026-04-27 16:27:15'),
(400,12,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u21a9 hhh\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 16:30:55',339,'App\\Models\\Message','2026-04-27 16:27:14','2026-04-27 16:30:55'),
(401,13,'message','{\"sender_name\":\"Admin\",\"sender_username\":\"admin\",\"sender_id\":3,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"\\u21a9 hhh\",\"message_type\":\"text\",\"conversation_id\":16}','2026-04-27 16:27:15',339,'App\\Models\\Message','2026-04-27 16:27:14','2026-04-27 16:27:15'),
(402,3,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdwd\",\"message_type\":\"text\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:34:23',340,'App\\Models\\Message','2026-04-27 16:33:40','2026-04-27 16:34:23'),
(403,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdwd\",\"message_type\":\"text\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:45:36',340,'App\\Models\\Message','2026-04-27 16:33:40','2026-04-27 16:45:36'),
(404,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"awdawd\",\"message_type\":\"text\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:45:36',341,'App\\Models\\Message','2026-04-27 16:33:55','2026-04-27 16:45:36'),
(405,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"adw\",\"message_type\":\"text\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:45:36',342,'App\\Models\\Message','2026-04-27 16:34:05','2026-04-27 16:45:36'),
(406,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"12e\",\"message_type\":\"text\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:45:36',343,'App\\Models\\Message','2026-04-27 16:34:09','2026-04-27 16:45:36'),
(407,12,'message','{\"sender_name\":\"mod3\",\"sender_username\":\"admin3\",\"sender_id\":13,\"sender_avatar\":\"http:\\/\\/192.168.1.15:8000\\/images\\/default-avatar.svg\",\"message_preview\":\"sent an image\",\"message_type\":\"image\",\"conversation_id\":16,\"is_group\":true,\"group_name\":\"awd\"}','2026-04-27 16:45:36',344,'App\\Models\\Message','2026-04-27 16:41:14','2026-04-27 16:45:36'),
(408,12,'follow','{\"follower_name\":\"admin\",\"follower_id\":3}',NULL,80,'App\\Models\\Follow','2026-04-27 16:46:10','2026-04-27 16:46:10'),
(409,12,'story_reaction','{\"reactor_name\":\"admin\",\"reactor_username\":\"admin\",\"reaction_type\":\"\\u2764\\ufe0f\",\"story_id\":8,\"story_slug\":\"erGEFsFkEJnybokirSGHMiEt\",\"story_owner_username\":\"admin2\"}',NULL,8,'App\\Models\\Story','2026-04-27 16:48:13','2026-04-27 16:48:13'),
(410,12,'story_reaction','{\"reactor_name\":\"admin\",\"reactor_username\":\"admin\",\"reaction_type\":\"\\u2764\\ufe0f\",\"story_id\":9,\"story_slug\":\"Ade0cXaWlIUFtfeu7zzohJhE\",\"story_owner_username\":\"admin2\"}',NULL,9,'App\\Models\\Story','2026-04-27 16:50:04','2026-04-27 16:50:04');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_media`
--

DROP TABLE IF EXISTS `post_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `media_thumbnail` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_media_post_id_foreign` (`post_id`),
  CONSTRAINT `post_media_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_media`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_media` WRITE;
/*!40000 ALTER TABLE `post_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_media` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_reactions`
--

DROP TABLE IF EXISTS `post_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `reaction_type` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_reactions_user_id_post_id_unique` (`user_id`,`post_id`),
  KEY `post_reactions_post_id_reaction_type_index` (`post_id`,`reaction_type`),
  CONSTRAINT `post_reactions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_reactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_reactions` WRITE;
/*!40000 ALTER TABLE `post_reactions` DISABLE KEYS */;
INSERT INTO `post_reactions` VALUES
(27,3,41,'❤️','2026-04-23 18:56:01','2026-04-25 12:59:13'),
(31,3,5,'👍','2026-04-25 12:34:50','2026-04-25 12:34:50'),
(32,12,41,'❤️','2026-04-25 13:07:27','2026-04-25 13:07:27');
/*!40000 ALTER TABLE `post_reactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_reports`
--

DROP TABLE IF EXISTS `post_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `admin_action` enum('delete','hide','warning','none') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_reports_slug_unique` (`slug`),
  KEY `post_reports_post_id_foreign` (`post_id`),
  KEY `post_reports_user_id_foreign` (`user_id`),
  KEY `post_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `post_reports_status_created_at_index` (`status`,`created_at`),
  KEY `post_reports_slug_index` (`slug`),
  CONSTRAINT `post_reports_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `post_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_reports` WRITE;
/*!40000 ALTER TABLE `post_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(24) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text DEFAULT NULL,
  `media_type` enum('image','video') DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `media_thumbnail` varchar(255) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `pinned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `posts_is_private_index` (`is_private`),
  KEY `posts_user_id_index` (`user_id`),
  KEY `posts_created_at_index` (`created_at`),
  KEY `posts_pinned_at_index` (`pinned_at`),
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(1,'3D6qg0MiwpYfSglMNKJMnb8Z',4,'Working on a new project. Excited to share more soon! 💻 #coding #developer',NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47',NULL),
(2,'ut45IoY7cvCxIUziK1uedIiQ',4,'Just had an amazing day exploring the city! 🌆 #adventure #citylife',NULL,NULL,NULL,0,NULL,'2026-03-25 22:59:47','2026-04-21 22:59:47',NULL),
(3,'glCFHcslnYKO9sJRUnTWYnNF',4,'New music alert! Been listening to this on repeat. 🎵 #music #newmusic',NULL,NULL,NULL,0,NULL,'2026-04-11 22:59:47','2026-04-21 22:59:47',NULL),
(4,'dpbSYps7Et4rHEUeJhOcvdfg',4,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-03-27 22:59:47','2026-04-21 22:59:47',NULL),
(5,'BdjIgkkirs9bsaGncHa9dZvi',4,'New music alert! Been listening to this on repeat. 🎵 #music #newmusic',NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47',NULL),
(6,'gOfZ7CshAsMjjaLbFEgGRA0o',5,'Beautiful sunset today! Nature never fails to amaze me. 🌅 #sunset #nature',NULL,NULL,NULL,0,NULL,'2026-03-25 22:59:47','2026-04-21 22:59:47',NULL),
(7,'yguMb6S7MKMfigKoWHvVgm2A',5,'Just read an amazing book. Highly recommend it! 📚 #reading #bookworm',NULL,NULL,NULL,0,NULL,'2026-03-30 22:59:47','2026-04-21 22:59:47',NULL),
(8,'G11VAaI7HEqoOw5EwelhHWeL',5,'Just discovered this amazing cafe! Must visit! ☕ #cafe #foodie',NULL,NULL,NULL,0,NULL,'2026-03-29 22:59:47','2026-04-21 22:59:47',NULL),
(9,'6qrWsaIgSzsf2E5VKDY5GF3S',5,'Morning coffee hits different ☕ #morningvibes #coffee',NULL,NULL,NULL,0,NULL,'2026-04-03 22:59:47','2026-04-21 22:59:47',NULL),
(10,'DBGo8ksof5MCClKGvKF9hdHS',6,'Morning coffee hits different ☕ #morningvibes #coffee',NULL,NULL,NULL,0,NULL,'2026-04-14 22:59:47','2026-04-21 22:59:47',NULL),
(11,'HnK9CM0zJWPnRBOqtC6EBJUO',6,'Grateful for all the little things in life. 🙏 #gratitude #blessed',NULL,NULL,NULL,0,NULL,'2026-04-08 22:59:47','2026-04-21 22:59:47',NULL),
(12,'pZrn67janTEzPZigdhBATgn1',6,'Just had an amazing day exploring the city! 🌆 #adventure #citylife',NULL,NULL,NULL,0,NULL,'2026-04-15 22:59:47','2026-04-21 22:59:47',NULL),
(13,'0KLdOQsLzdIYBOLJUKzMnvdY',7,'New music alert! Been listening to this on repeat. 🎵 #music #newmusic',NULL,NULL,NULL,0,NULL,'2026-04-15 22:59:47','2026-04-21 22:59:47',NULL),
(14,'q4UjW7sOfIYA1dzO5tSJtwaN',7,'Grateful for all the little things in life. 🙏 #gratitude #blessed',NULL,NULL,NULL,0,NULL,'2026-04-05 22:59:47','2026-04-21 22:59:47',NULL),
(15,'kUw5r9tbQ8YV7J8fAXmIRjRa',7,'Nothing beats a good walk in the park. 🌳 #nature #wellness',NULL,NULL,NULL,0,NULL,'2026-03-25 22:59:47','2026-04-21 22:59:47',NULL),
(16,'MIB3ru5aafJYMYk4dCma9Ah0',7,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-03-23 22:59:47','2026-04-21 22:59:47',NULL),
(17,'X30ljyNZPA8ghk0bnNDWPUbc',7,'New music alert! Been listening to this on repeat. 🎵 #music #newmusic',NULL,NULL,NULL,0,NULL,'2026-04-02 22:59:47','2026-04-21 22:59:47',NULL),
(18,'lXeRDmciDDsj6O94CV4a3BSu',7,'Just discovered this amazing cafe! Must visit! ☕ #cafe #foodie',NULL,NULL,NULL,0,NULL,'2026-04-07 22:59:47','2026-04-21 22:59:47',NULL),
(19,'TyyjOgCXPoARkUvqX2mFvJHC',8,'Weekend vibes! Time to relax and recharge. 😌 #weekend #selfcare',NULL,NULL,NULL,0,NULL,'2026-03-25 22:59:47','2026-04-21 22:59:47',NULL),
(20,'ClFgAIJdU8jmsME7t6pNv9OA',8,'Just had an amazing day exploring the city! 🌆 #adventure #citylife',NULL,NULL,NULL,0,NULL,'2026-04-07 22:59:47','2026-04-21 22:59:47',NULL),
(21,'aAAxTbO0cW64GdPuoieaIaOn',8,'Late night coding session. The grind never stops! 🌙 #developer #hustle',NULL,NULL,NULL,0,NULL,'2026-04-15 22:59:47','2026-04-21 22:59:47',NULL),
(22,'WtTg8ltbM5kVg6uPeQ3yDFsa',8,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-03-27 22:59:47','2026-04-21 22:59:47',NULL),
(23,'mWEuPnSuyzCrKJDZL7Pn89qP',8,'Just read an amazing book. Highly recommend it! 📚 #reading #bookworm',NULL,NULL,NULL,0,NULL,'2026-04-08 22:59:47','2026-04-21 22:59:47',NULL),
(24,'uv1vfHFxeDhMdY7FAwZZHOpc',8,'Just discovered this amazing cafe! Must visit! ☕ #cafe #foodie',NULL,NULL,NULL,0,NULL,'2026-04-12 22:59:47','2026-04-21 22:59:47',NULL),
(25,'tFdEw8wA5A5gupSsgGRn5h3w',8,'Weekend vibes! Time to relax and recharge. 😌 #weekend #selfcare',NULL,NULL,NULL,0,NULL,'2026-04-10 22:59:47','2026-04-21 22:59:47',NULL),
(26,'bBMRTnKXM2iMrOXIHjvSAy30',8,'Weekend vibes! Time to relax and recharge. 😌 #weekend #selfcare',NULL,NULL,NULL,0,NULL,'2026-03-25 22:59:47','2026-04-21 22:59:47',NULL),
(27,'4G7OouhgkV5y9ormqrb4CAI0',9,'Grateful for all the little things in life. 🙏 #gratitude #blessed',NULL,NULL,NULL,0,NULL,'2026-03-24 22:59:47','2026-04-21 22:59:47',NULL),
(28,'AFZXSdPahh67Hsy42E2H5GmH',9,'Beautiful sunset today! Nature never fails to amaze me. 🌅 #sunset #nature',NULL,NULL,NULL,0,NULL,'2026-03-28 22:59:47','2026-04-21 22:59:47',NULL),
(29,'sIHiRMxh75e4ZtaesOu2ZxEe',9,'Grateful for all the little things in life. 🙏 #gratitude #blessed',NULL,NULL,NULL,0,NULL,'2026-04-02 22:59:47','2026-04-21 22:59:47',NULL),
(30,'ha3xpCClCYQhjxcjIRQsQf2v',9,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-03-30 22:59:47','2026-04-21 22:59:47',NULL),
(31,'NB7a1zjRaWlWrjre8rQ2ihKA',9,'Working on a new project. Excited to share more soon! 💻 #coding #developer',NULL,NULL,NULL,0,NULL,'2026-04-02 22:59:47','2026-04-21 22:59:47',NULL),
(32,'xIWpzkiFKOgmeqc2fSjAS60A',10,'Just discovered this amazing cafe! Must visit! ☕ #cafe #foodie',NULL,NULL,NULL,0,NULL,'2026-04-15 22:59:47','2026-04-21 22:59:47',NULL),
(33,'2Hwk2GJNoOVYR4apjYj7rAGU',10,'Starting a new journey today! Wish me luck! 🚀 #newbeginnings #goals',NULL,NULL,NULL,0,NULL,'2026-03-23 22:59:47','2026-04-21 22:59:47',NULL),
(34,'l2CErmkM5s3NJ3z6v4vCKfkT',10,'Working on a new project. Excited to share more soon! 💻 #coding #developer',NULL,NULL,NULL,0,NULL,'2026-04-10 22:59:47','2026-04-21 22:59:47',NULL),
(35,'dBCKPiPyvnMbEyFuExnD8gyw',10,'Morning coffee hits different ☕ #morningvibes #coffee',NULL,NULL,NULL,0,NULL,'2026-04-13 22:59:47','2026-04-21 22:59:47',NULL),
(36,'zOmsxh9yv392nlvaEozSKO4w',11,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-04-15 22:59:47','2026-04-21 22:59:47',NULL),
(37,'5kCcQul8eYYsmamtTHPflqaL',11,'Starting a new journey today! Wish me luck! 🚀 #newbeginnings #goals',NULL,NULL,NULL,0,NULL,'2026-04-10 22:59:47','2026-04-21 22:59:47',NULL),
(38,'fbdbZkS0P42dEuD8Hbjenccq',11,'Working on a new project. Excited to share more soon! 💻 #coding #developer',NULL,NULL,NULL,0,NULL,'2026-04-07 22:59:47','2026-04-21 22:59:47',NULL),
(39,'wE2S5Vs6VbDF6zJayIpbQQY9',11,'Family time is the best time! ❤️ #family #love',NULL,NULL,NULL,0,NULL,'2026-04-03 22:59:47','2026-04-21 22:59:47',NULL),
(40,'NWyyy35cyqEUkeJ6Z27JWSto',11,'Grateful for all the little things in life. 🙏 #gratitude #blessed',NULL,NULL,NULL,0,NULL,'2026-04-17 22:59:47','2026-04-21 22:59:47',NULL),
(41,'7o0LeaZamWZdoQeSFgtKW1E4',3,'awd',NULL,NULL,NULL,0,NULL,'2026-04-21 23:00:56','2026-04-21 23:00:56',NULL),
(42,'sWqeZ2T4SxxHau4VvQpAdIoB',12,'adw',NULL,NULL,NULL,1,NULL,'2026-04-22 16:06:53','2026-04-22 16:07:05','2026-04-22 16:07:05'),
(43,'I98A27pyEwCgGbPYKCyMUU1j',3,'ad',NULL,NULL,NULL,1,NULL,'2026-04-25 12:59:01','2026-04-25 12:59:06','2026-04-25 12:59:06');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profiles_user_id_unique` (`user_id`),
  CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `profiles` WRITE;
/*!40000 ALTER TABLE `profiles` DISABLE KEYS */;
INSERT INTO `profiles` VALUES
(3,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:45','2026-04-27 11:34:18'),
(4,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:45','2026-04-21 22:59:45'),
(5,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:45','2026-04-21 22:59:45'),
(6,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(7,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(8,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(9,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(10,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(11,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(12,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-21 23:02:08','2026-04-21 23:02:08'),
(14,13,NULL,NULL,'Administrator Account',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-04-27 12:38:39','2026-04-27 12:38:39');
/*!40000 ALTER TABLE `profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `content_encoding` varchar(255) NOT NULL DEFAULT 'aesgcm',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_subscriptions_user_id_index` (`user_id`),
  KEY `user_id` (`user_id`),
  KEY `endpoint` (`endpoint`(255)),
  CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `saved_posts`
--

DROP TABLE IF EXISTS `saved_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_posts_user_id_post_id_unique` (`user_id`,`post_id`),
  KEY `saved_posts_post_id_foreign` (`post_id`),
  CONSTRAINT `saved_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `saved_posts` WRITE;
/*!40000 ALTER TABLE `saved_posts` DISABLE KEYS */;
INSERT INTO `saved_posts` VALUES
(1,5,25,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(2,6,14,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(3,6,6,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(4,6,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(5,8,16,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(6,10,40,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(7,10,18,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(8,10,12,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(9,11,32,'2026-04-21 22:59:48','2026-04-21 22:59:48'),
(10,11,6,'2026-04-21 22:59:48','2026-04-21 22:59:48');
/*!40000 ALTER TABLE `saved_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('etrjKTiFQp54gTTUBs7w7ZBT6k9Mmxkuf7BuOZK6',13,'192.168.1.15','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','YTo5OntzOjY6Il90b2tlbiI7czo0MDoia0pRNjZ5cEpnRU8wNVRJOVQ2cU96eWxRVUpWNDEySDlSOWs1N3pSNCI7czo2OiJsb2NhbGUiO3M6MjoiZW4iO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQzOiJodHRwOi8vMTkyLjE2OC4xLjE1OjgwMDAvY2hhdC9jb252ZXJzYXRpb25zIjtzOjU6InJvdXRlIjtzOjE4OiJjaGF0LmNvbnZlcnNhdGlvbnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxMztzOjEwOiJzZXNzaW9uX2lkIjtzOjQwOiJldHJqS1RpRlFwNTRnVFRVQnM3dzdaQlQ2azlNbXhrdWY3QnVPWks2IjtzOjEzOiJsYXN0X2FjdGl2aXR5IjtpOjE3NzcyOTM1NDE7czoxMDoibG9naW5fdGltZSI7czoxOToiMjAyNi0wNC0yNyAxNTozOTowMSI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRCN2tTWjZtL2ZCdTRFRG1iOS5hbEllR3N4dy44ZC9lT1EyZlVicnhBTFBvODJhT0ExRm1HNiI7fQ==',1777308112),
('GUSPqEJcBZosvW3Layo1TcoGrG0OQF3n45Rf9w1l',12,'192.168.1.2','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiMWs1dkFDVW9NTmFZajFsRkcwbjY3a29nN1RRTDJyMGp4Y2h5YTdwZSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTI7czo2OiJsb2NhbGUiO3M6MjoiZW4iO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQyOiJodHRwOi8vMTkyLjE2OC4xLjE1OjgwMDAvYXBpL25vdGlmaWNhdGlvbnMiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkUDNGOEtPSlJDcXk1Uzl4WFNlWFlsZU0xdDhyL2M5VDMvVHBOUWR0T0ppVWtRZ2l3elJSV0MiO30=',1777308604),
('qLi0HQj22lsdPCRofQclA9qfXUmnEeyYhBrf5NjM',3,'192.168.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','YTo5OntzOjY6Il90b2tlbiI7czo0MDoid3dpU3ZnZGdRclJKQzBoalJheGwwWE9SZGNHOTNrSXlEMGZ4eUx3ZSI7czo2OiJsb2NhbGUiO3M6MjoiZW4iO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQzOiJodHRwOi8vMTkyLjE2OC4xLjE1OjgwMDAvY2hhdC9jb252ZXJzYXRpb25zIjtzOjU6InJvdXRlIjtzOjE4OiJjaGF0LmNvbnZlcnNhdGlvbnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO3M6MTA6InNlc3Npb25faWQiO3M6NDA6InFMaTBIUWoyMmxzZFBDUm9mUWNsQTlxZlhVbW5FZXlZaEJyZjVOak0iO3M6MTM6Imxhc3RfYWN0aXZpdHkiO2k6MTc3NzI4ODkxNjtzOjEwOiJsb2dpbl90aW1lIjtzOjE5OiIyMDI2LTA0LTI3IDE0OjIxOjU2IjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJEthalEzcUxPTlFFWnJlV05iYndQQ09FbFVDNmROUm9rcG9lUmtRYjh5Sk1lVTVIRFBCSXBHIjt9',1777309913),
('uWaeIhROCa5xZT8hZMOkebWJk56WnG01rdPWflcH',NULL,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTmw4cWdWTEw2RGdIcGs0M3BwaVY0ajFVdWplQUN4eHdhMGxhR1BqYSI7czo2OiJsb2NhbGUiO3M6MjoiZW4iO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6MTA6ImxvZ2luLnZpZXciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1777304382);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `stories`
--

DROP TABLE IF EXISTS `stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `media_type` enum('image','video','text') DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stories_slug_unique` (`slug`),
  KEY `stories_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `stories_expires_at_index` (`expires_at`),
  KEY `stories_user_id_index` (`user_id`),
  CONSTRAINT `stories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stories` WRITE;
/*!40000 ALTER TABLE `stories` DISABLE KEYS */;
INSERT INTO `stories` VALUES
(1,9,'image','stories/sample_gOuGx1tQGv.jpg','Coffee time ☕',NULL,'2026-04-22 22:59:48','2026-04-21 22:59:48','2026-04-21 22:59:48',0,'EXzPgYIMGdOqxFuZ1EEsCBFM'),
(2,5,'image','stories/sample_xO56OStGBH.jpg','Coffee time ☕',NULL,'2026-04-22 22:59:48','2026-04-21 22:59:48','2026-04-21 22:59:48',0,'rYBnm8G9ZtuxLMldRRUtpzrL'),
(3,4,'image','stories/sample_awYmB4A4pC.jpg','New adventure begins! 🚀',NULL,'2026-04-22 22:59:48','2026-04-21 22:59:48','2026-04-21 22:59:48',0,'w1xS0Qtwy6gsZGILLYAbM6CN'),
(4,11,'image','stories/sample_dtKeJfAtCZ.jpg','Having a great day! 😊',NULL,'2026-04-22 22:59:48','2026-04-21 22:59:48','2026-04-21 22:59:48',0,'2pGz8elKXnPjg8DaQ0WdGuvK'),
(5,10,'image','stories/sample_2d5nqDf3DA.jpg','Workout done! 💪',NULL,'2026-04-22 22:59:48','2026-04-21 22:59:48','2026-04-21 22:59:48',0,'Es4zEIX6d6ZNEgazvEdtqm6r'),
(9,12,'text',NULL,'هفت','{\"bg_color\":\"linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)\"}','2026-04-28 16:49:58','2026-04-27 16:49:58','2026-04-27 16:50:01',1,'Ade0cXaWlIUFtfeu7zzohJhE');
/*!40000 ALTER TABLE `stories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `story_reactions`
--

DROP TABLE IF EXISTS `story_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `story_id` bigint(20) unsigned NOT NULL,
  `reaction_type` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_reactions_user_id_story_id_unique` (`user_id`,`story_id`),
  KEY `story_reactions_story_id_foreign` (`story_id`),
  CONSTRAINT `story_reactions_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_reactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `story_reactions` WRITE;
/*!40000 ALTER TABLE `story_reactions` DISABLE KEYS */;
INSERT INTO `story_reactions` VALUES
(3,3,9,'❤️','2026-04-27 16:50:04','2026-04-27 16:50:04');
/*!40000 ALTER TABLE `story_reactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `story_views`
--

DROP TABLE IF EXISTS `story_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `story_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_views_user_id_story_id_unique` (`user_id`,`story_id`),
  KEY `story_views_story_id_foreign` (`story_id`),
  CONSTRAINT `story_views_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_views`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `story_views` WRITE;
/*!40000 ALTER TABLE `story_views` DISABLE KEYS */;
INSERT INTO `story_views` VALUES
(3,3,9,'2026-04-27 16:50:01','2026-04-27 16:50:01');
/*!40000 ALTER TABLE `story_views` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `username_changed_at` timestamp NULL DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `last_active` timestamp NULL DEFAULT NULL,
  `inactive_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(6) DEFAULT NULL,
  `verification_code_expires_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_is_online_index` (`is_online`),
  KEY `users_last_active_index` (`last_active`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(3,'Admin','admin',NULL,'admin@example.com','2026-04-21 22:59:45','en','2026-04-27 17:11:56',NULL,0,NULL,NULL,1,0,'$2y$12$KajQ3qLONQEZreWNbbwPCOElUC6dNRokpoeRkQb8yJMeU5HDPBIpG','eYKDg1EpaOcSoEOppwBONbEulP07yQmHQiJaSWJJZoYkQK7NDYsnbD1TYZBz','2026-04-21 22:59:45','2026-04-27 17:11:56'),
(4,'John Doe','johndoe',NULL,'john@example.com','2026-04-21 22:59:45','en','2026-04-21 22:59:45',NULL,0,NULL,NULL,0,0,'$2y$12$ta7RHoupC9vWIaaJjWSFLu.PdMQd/5OZVoM..p4Lyyi6nMLeK.Yz6',NULL,'2026-04-21 22:59:45','2026-04-21 22:59:45'),
(5,'Jane Smith','janesmith',NULL,'jane@example.com','2026-04-21 22:59:45','en','2026-04-21 22:59:45',NULL,0,NULL,NULL,0,0,'$2y$12$c5c4UXdhfYqq54wtJBqhcOTv6ATihdMRAIaIULdwf/jyMbVvsN1XC',NULL,'2026-04-21 22:59:45','2026-04-21 22:59:45'),
(6,'Mike Johnson','mikej',NULL,'mike@example.com','2026-04-21 22:59:46','en','2026-04-21 22:59:46',NULL,0,NULL,NULL,0,0,'$2y$12$Wp5JaoWFenZ5487x8UbhPeXjRZhBOTdipZrKbR6K2gnOpJCUd4U0S',NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(7,'Emily Davis','emilyd',NULL,'emily@example.com','2026-04-21 22:59:46','en','2026-04-21 22:59:46',NULL,0,NULL,NULL,0,0,'$2y$12$leXKKbY1ka5LiVMea2KvoOi/loYC9YQwoTwXPZ1uk4IPE.ow/KXpS',NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(8,'Chris Wilson','chrisw',NULL,'chris@example.com','2026-04-21 22:59:46','en','2026-04-21 22:59:46',NULL,0,NULL,NULL,0,0,'$2y$12$VUptwcme/0uyrY9Osz/5r.dIiZNqgTPXTPkrdZi0UYPO1qibWhuaW',NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(9,'Sarah Brown','sarahb',NULL,'sarah@example.com','2026-04-21 22:59:46','en','2026-04-21 22:59:46',NULL,0,NULL,NULL,0,0,'$2y$12$VRyIHE5XFFODFF377Zkry.eywW9vqNpc8C0K3w8OR1z87axHQyH0e',NULL,'2026-04-21 22:59:46','2026-04-21 22:59:46'),
(10,'David Lee','davidl',NULL,'david@example.com','2026-04-21 22:59:47','en','2026-04-21 22:59:47',NULL,0,NULL,NULL,0,0,'$2y$12$gcBbwia9nZ0vrvIBjtpfAO0afEr.EkXmogLqCtz4K6eGBlGF51toO',NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(11,'Lisa Anderson','lisaa',NULL,'lisa@example.com','2026-04-21 22:59:47','en','2026-04-21 22:59:47',NULL,0,NULL,NULL,0,0,'$2y$12$l02JT/pfn5dwhQKooLuX8O1l4pPZ31TQMCrAR.7SofDWbs.bTwona',NULL,'2026-04-21 22:59:47','2026-04-21 22:59:47'),
(12,'admin2','admin2',NULL,'admin2@example.com','2026-04-21 23:06:01','en','2026-04-27 16:56:44',NULL,0,NULL,NULL,1,0,'$2y$12$P3F8KOJRCqy5S9xXSeXYleM1t8r/c9T3/TpNQdtOJiUkQgiwzRRWC','njRhYIWNHbhBhv7MOXY6EC0xAKWf1GbuARSZ3Z6u7uOR7jE05Wd6pSGkLANv','2026-04-21 23:02:08','2026-04-27 16:56:44'),
(13,'mod3','admin3',NULL,'admin3@example.com','2026-04-27 12:38:39','en','2026-04-27 16:42:00',NULL,0,NULL,NULL,1,0,'$2y$12$B7kSZ6m/fBu4EDmb9.alIeGsxw.8d/eOQ2fUbrxALPo82aOA1FmG6',NULL,'2026-04-27 12:38:39','2026-04-27 16:42:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-27 21:54:01
