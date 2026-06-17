-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: TW4_base
-- ------------------------------------------------------
-- Server version	8.0.45

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
-- Current Database: `TW4_base`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_base` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_base`;

--
-- Table structure for table `application_log`
--

DROP TABLE IF EXISTS `application_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_log` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `level` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `context` json DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_level` (`level`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_log`
--

LOCK TABLES `application_log` WRITE;
/*!40000 ALTER TABLE `application_log` DISABLE KEYS */;
INSERT INTO `application_log` VALUES (38,'2026-06-06 21:42:47','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:42:47'),(39,'2026-06-06 21:43:28','INFO','SYSTEM','System status automatically set to \'ready\' after configuration update','{\"updated_by\": \"admin\", \"changes_count\": 1}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:43:28'),(40,'2026-06-06 21:43:28','INFO','CONFIG','Configuration updated','{\"changes\": {\"timestamp\": \"2026-06-06 21:43:28\", \"updated_by\": \"admin\", \"changes_count\": 1, \"changed_configs\": {\"entry_fee\": {\"type\": \"int\", \"config_id\": \"9\", \"new_value\": \"6\", \"old_value\": \"5\"}}}}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:43:28'),(41,'2026-06-06 21:43:28','INFO','SYSTEM','Configuration updated: entry_fee','{\"type\": \"int\", \"new_value\": \"6\", \"old_value\": \"5\", \"updated_by\": \"admin\", \"config_name\": \"entry_fee\"}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:43:28'),(42,'2026-06-06 21:44:38','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:44:38'),(43,'2026-06-06 21:44:52','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:44:52'),(44,'2026-06-06 21:44:59','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-06\", \"season_year\": \"25_26\", \"round_number\": 1, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:44:59'),(45,'2026-06-06 21:47:35','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-06 21:47:35\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:47:35'),(46,'2026-06-06 21:47:52','INFO','SYSTEM','Scoring workflow changed to finished (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": \"2026-06-06 21:47:52\", \"after_card_count\": 4, \"before_card_count\": 4, \"after_workflow_step\": \"finished\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:47:52'),(47,'2026-06-06 21:47:52','INFO','SYSTEM','Handicap updates applied before history snapshot','{\"method\": \"modern\", \"round_id\": 2, \"staff_id\": 2, \"player_handicap_changes\": [{\"change\": -7, \"player_id\": 1, \"card_row_id\": 10, \"points_adjusted\": 27, \"handicap_applied\": 12, \"handicap_updated\": 5, \"player_identifier\": \"P1\"}, {\"change\": -7, \"player_id\": 2, \"card_row_id\": 11, \"points_adjusted\": 27, \"handicap_applied\": 15, \"handicap_updated\": 8, \"player_identifier\": \"P2\"}, {\"change\": -7, \"player_id\": 3, \"card_row_id\": 12, \"points_adjusted\": 27, \"handicap_applied\": 18, \"handicap_updated\": 11, \"player_identifier\": \"P3\"}, {\"change\": -9, \"player_id\": 4, \"card_row_id\": 13, \"points_adjusted\": 29, \"handicap_applied\": 20, \"handicap_updated\": 11, \"player_identifier\": \"P4\"}]}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-06 21:47:52'),(48,'2026-06-09 08:04:56','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','scorer1','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:04:56'),(49,'2026-06-09 08:05:28','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','Scorer1','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:05:28'),(50,'2026-06-09 08:05:30','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','scorer1','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:05:30'),(51,'2026-06-09 08:05:36','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:05:36'),(52,'2026-06-09 08:05:41','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:05:41'),(53,'2026-06-09 08:06:26','INFO','CONFIG','Configuration staff_updated','{\"changes\": {\"role\": \"scorer\", \"row_id\": 2, \"username\": \"scorer\", \"password_changed\": true}}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:06:26'),(54,'2026-06-09 08:06:50','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:06:50'),(55,'2026-06-09 08:06:55','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:06:55'),(56,'2026-06-09 08:07:13','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:07:13'),(57,'2026-06-09 08:07:23','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:07:23'),(58,'2026-06-09 08:07:34','WARNING','SYSTEM','Admin forced release of scoring lock (state applied)','{\"round_id\": 2, \"rows_updated\": 1, \"admin_staff_id\": 1, \"after_workflow_step\": \"finished\", \"before_workflow_step\": \"finished\", \"after_locked_by_staff_id\": null, \"after_lock_release_reason\": \"admin_forced\", \"before_locked_by_staff_id\": null, \"before_lock_release_reason\": \"finished\"}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:07:34'),(59,'2026-06-09 08:08:35','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:08:35'),(60,'2026-06-09 08:08:43','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:08:43'),(61,'2026-06-09 08:10:17','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 2, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:10:17'),(62,'2026-06-09 08:12:51','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 08:12:51\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:12:51'),(63,'2026-06-09 08:12:59','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:12:59'),(64,'2026-06-09 08:24:05','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:24:05'),(65,'2026-06-09 08:24:11','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:24:11'),(66,'2026-06-09 08:24:15','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 3, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:24:15'),(67,'2026-06-09 08:25:07','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 08:25:07\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:07'),(68,'2026-06-09 08:25:12','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:12'),(69,'2026-06-09 08:25:12','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 3}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:12'),(70,'2026-06-09 08:25:12','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/003_Jun_09\", \"round_slug\": \"003_Jun_09\", \"season_year\": \"25_26\", \"round_number\": 3, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:12'),(71,'2026-06-09 08:25:26','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:26'),(72,'2026-06-09 08:25:32','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:25:32'),(73,'2026-06-09 08:31:45','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:31:45'),(74,'2026-06-09 08:31:50','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:31:50'),(75,'2026-06-09 08:34:46','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 4, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:34:46'),(76,'2026-06-09 08:35:10','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:35:10'),(77,'2026-06-09 08:36:24','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:36:24'),(78,'2026-06-09 08:45:21','INFO','CONFIG','Configuration course_played_updated','{\"changes\": {\"row_id\": 2, \"name_club\": \"OVGC\", \"name_course\": \"Blues\", \"ident_eclectic\": \"Twilight\"}}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:45:21'),(79,'2026-06-09 08:45:37','INFO','CONFIG','Configuration course_played_updated','{\"changes\": {\"row_id\": 1, \"name_club\": \"OVGC\", \"name_course\": \"Whites\", \"ident_eclectic\": \"Twilight\"}}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:45:37'),(80,'2026-06-09 08:45:45','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:45:45'),(81,'2026-06-09 08:45:51','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:45:51'),(82,'2026-06-09 08:48:28','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 08:48:28\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:48:28'),(83,'2026-06-09 08:48:33','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 5, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:48:33'),(84,'2026-06-09 08:48:33','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 4}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:48:33'),(85,'2026-06-09 08:48:33','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/004_Jun_09\", \"round_slug\": \"004_Jun_09\", \"season_year\": \"25_26\", \"round_number\": 4, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:48:33'),(86,'2026-06-09 08:51:28','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 5, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 08:51:28'),(87,'2026-06-09 09:00:52','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 09:00:52\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:00:52'),(88,'2026-06-09 09:00:57','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 5, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:00:57'),(89,'2026-06-09 09:00:57','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 5}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:00:57'),(90,'2026-06-09 09:00:57','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/005_Jun_09\", \"round_slug\": \"005_Jun_09\", \"season_year\": \"25_26\", \"round_number\": 5, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:00:57'),(91,'2026-06-09 09:10:45','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 6, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:10:45'),(92,'2026-06-09 09:13:45','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 09:13:45\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:13:45'),(93,'2026-06-09 09:14:01','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 5, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:14:01'),(94,'2026-06-09 09:14:01','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 6}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:14:01'),(95,'2026-06-09 09:14:01','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/006_Jun_09\", \"round_slug\": \"006_Jun_09\", \"season_year\": \"25_26\", \"round_number\": 6, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:14:01'),(96,'2026-06-09 09:19:19','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-09\", \"season_year\": \"25_26\", \"round_number\": 7, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:19:19'),(97,'2026-06-09 09:21:21','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-09 09:21:21\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:21:21'),(98,'2026-06-09 09:21:45','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:21:45'),(99,'2026-06-09 09:21:45','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:21:45'),(100,'2026-06-09 09:21:45','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/007_Jun_09\", \"round_slug\": \"007_Jun_09\", \"season_year\": \"25_26\", \"round_number\": 7, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-09 09:21:45'),(101,'2026-06-10 07:35:33','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 07:35:33'),(102,'2026-06-10 07:35:42','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 07:35:42'),(103,'2026-06-10 07:48:59','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-10 07:48:59'),(104,'2026-06-10 07:51:29','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.123.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36',NULL,'2026-06-10 07:51:29'),(105,'2026-06-10 08:34:53','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-10\", \"season_year\": \"25_26\", \"round_number\": 8, \"course_played_id\": 2, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:34:53'),(106,'2026-06-10 08:37:41','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-10 08:37:41\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:37:41'),(107,'2026-06-10 08:37:58','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:37:58'),(108,'2026-06-10 08:37:58','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 8}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:37:58'),(109,'2026-06-10 08:37:58','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/008_Jun_10\", \"round_slug\": \"008_Jun_10\", \"season_year\": \"25_26\", \"round_number\": 8, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:37:58'),(110,'2026-06-10 08:45:53','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:45:53'),(111,'2026-06-10 08:45:57','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:45:57'),(112,'2026-06-10 08:46:02','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-10\", \"season_year\": \"25_26\", \"round_number\": 9, \"course_played_id\": 2, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:46:02'),(113,'2026-06-10 08:47:20','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-10 08:47:20\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:47:20'),(114,'2026-06-10 08:47:25','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:47:25'),(115,'2026-06-10 08:47:25','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 9}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:47:25'),(116,'2026-06-10 08:47:25','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/009_Jun_10\", \"round_slug\": \"009_Jun_10\", \"season_year\": \"25_26\", \"round_number\": 9, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-10 08:47:25'),(117,'2026-06-11 08:48:35','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:48:35'),(118,'2026-06-11 08:48:50','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:48:50'),(119,'2026-06-11 08:48:55','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:48:55'),(120,'2026-06-11 08:49:00','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:00'),(121,'2026-06-11 08:49:11','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin1','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:11'),(122,'2026-06-11 08:49:28','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:28'),(123,'2026-06-11 08:49:33','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:33'),(124,'2026-06-11 08:49:34','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:34'),(125,'2026-06-11 08:49:48','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:48'),(126,'2026-06-11 08:49:59','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:49:59'),(127,'2026-06-11 08:50:09','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:50:09'),(128,'2026-06-11 08:50:09','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin1','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:50:09'),(129,'2026-06-11 08:51:54','WARNING','LOGIN','Login failed','{\"reason\": \"Invalid credentials\", \"success\": false}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:51:54'),(130,'2026-06-11 08:52:03','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:52:03'),(131,'2026-06-11 08:55:02','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:55:02'),(132,'2026-06-11 08:55:06','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:55:06'),(133,'2026-06-11 08:55:50','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:55:50'),(134,'2026-06-11 08:55:53','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:55:53'),(135,'2026-06-11 08:57:57','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-11 08:57:57\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:57:57'),(136,'2026-06-11 08:58:01','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 4, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:58:01'),(137,'2026-06-11 08:58:01','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 840}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:58:01'),(138,'2026-06-11 08:58:01','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/840_Jun_11\", \"round_slug\": \"840_Jun_11\", \"season_year\": \"25_26\", \"round_number\": 840, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:58:01'),(139,'2026-06-11 08:59:28','INFO','SYSTEM','Scoring workflow changed to card_entry_open (round started, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"round_date\": \"2026-06-11\", \"season_year\": \"25_26\", \"round_number\": 841, \"course_played_id\": 1, \"after_workflow_step\": \"card_entry_open\", \"before_workflow_step\": \"not_started\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 08:59:28'),(140,'2026-06-11 09:04:09','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-11 09:04:09\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:04:09'),(141,'2026-06-11 09:04:14','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 6, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:04:14'),(142,'2026-06-11 09:04:14','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 841}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:04:14'),(143,'2026-06-11 09:04:14','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/841_Jun_11\", \"round_slug\": \"841_Jun_11\", \"season_year\": \"25_26\", \"round_number\": 841, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:04:14'),(144,'2026-06-11 09:22:01','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:22:01'),(145,'2026-06-11 09:22:05','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:22:05'),(146,'2026-06-11 09:22:35','WARNING','SYSTEM','Admin forced release of scoring lock (state applied)','{\"round_id\": 2, \"rows_updated\": 1, \"admin_staff_id\": 1, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"not_started\", \"after_locked_by_staff_id\": null, \"after_lock_release_reason\": \"admin_forced\", \"before_locked_by_staff_id\": null, \"before_lock_release_reason\": \"finished\"}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:22:35'),(147,'2026-06-11 09:22:49','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:22:49'),(148,'2026-06-11 09:22:54','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:22:54'),(149,'2026-06-11 09:23:07','INFO','LOGOUT','User logged out',NULL,'scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:23:07'),(150,'2026-06-11 09:23:15','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:23:15'),(151,'2026-06-11 09:31:42','WARNING','SYSTEM','Admin reset scoring state from not_started to card_entry_open (state applied)','{\"to_step\": \"card_entry_open\", \"round_id\": 2, \"from_step\": \"not_started\", \"card_count\": 6, \"admin_staff_id\": 1, \"applied_card_count\": 6, \"results_rows_cleared\": 6, \"applied_workflow_step\": \"card_entry_open\", \"applied_locked_by_staff_id\": null, \"applied_lock_release_reason\": \"admin_forced\"}','admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:31:42'),(152,'2026-06-11 09:31:53','INFO','LOGOUT','User logged out',NULL,'admin','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:31:53'),(153,'2026-06-11 09:31:57','INFO','LOGIN','Login successful','{\"reason\": \"\", \"success\": true}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:31:57'),(154,'2026-06-11 09:32:40','INFO','SYSTEM','Scoring workflow changed to results_presented (state applied)','{\"round_id\": 2, \"staff_id\": 2, \"after_workflow_step\": \"results_presented\", \"before_workflow_step\": \"card_entry_open\", \"results_presented_at\": \"2026-06-11 09:32:40\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:32:40'),(155,'2026-06-11 09:32:44','INFO','SYSTEM','Scoring workflow changed to not_started (round finished, state applied)','{\"round_id\": 2, \"staff_id\": 2, \"finished_at\": null, \"after_card_count\": 0, \"before_card_count\": 6, \"after_workflow_step\": \"not_started\", \"before_workflow_step\": \"results_presented\"}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:32:44'),(156,'2026-06-11 09:32:44','INFO','SYSTEM','Extract season_year and round_number for export','{\"active_keys\": [\"round_id\", \"season_year\", \"round_number\", \"round_date\", \"course_played_id\", \"workflow_step\", \"card_count\", \"locked_by_staff_id\", \"lock_acquired_at\", \"lock_expires_at\", \"results_presented_at\", \"finished_at\"], \"season_year\": \"25_26\", \"round_number\": 841}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:32:44'),(157,'2026-06-11 09:32:44','INFO','SYSTEM','Round snapshots exported after finish round','{\"directory\": \"/var/www/html/public/reports/25_26/841\", \"round_slug\": \"841\", \"season_year\": \"25_26\", \"round_number\": 841, \"written_count\": 7}','scorer','172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0',NULL,'2026-06-11 09:32:44');
/*!40000 ALTER TABLE `application_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL,
  `record_id` int DEFAULT NULL,
  `action` enum('create','update','delete','login','logout') NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_table` (`table_name`),
  KEY `idx_action` (`action`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_application`
--

DROP TABLE IF EXISTS `config_application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_application` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `config_name` varchar(100) NOT NULL,
  `config_value_string` text,
  `config_value_int` int DEFAULT NULL,
  `config_type` enum('string','int') NOT NULL DEFAULT 'string',
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `config_name` (`config_name`),
  KEY `idx_config_name` (`config_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_application`
--

LOCK TABLES `config_application` WRITE;
/*!40000 ALTER TABLE `config_application` DISABLE KEYS */;
INSERT INTO `config_application` VALUES (1,'team_haggle_state','F',0,'string','admin','2026-05-07 08:17:03'),(2,'club_number','294',294,'int','admin','2026-05-07 08:17:05'),(3,'config_status','ready',NULL,'string','system','2026-05-07 08:17:50'),(4,'club_name','TW4 Golf Club',NULL,'string','admin','2026-05-07 08:17:50'),(5,'competition_name','Twilight',NULL,'string','admin','2026-05-07 08:17:50'),(6,'season_year','25_26',NULL,'string','admin','2026-05-07 08:17:50'),(8,'max_handicap','54',54,'int','admin','2026-05-07 08:17:50'),(9,'entry_fee','6',6,'int','admin','2026-05-07 08:17:50'),(10,'handicap_method','modern',NULL,'string','admin','2026-05-07 08:41:14');
/*!40000 ALTER TABLE `config_application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_club`
--

DROP TABLE IF EXISTS `course_club`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_club` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `name_club` varchar(16) NOT NULL,
  `gender` char(1) NOT NULL,
  `number_hole` int NOT NULL,
  `name_hole` varchar(24) NOT NULL,
  `par` int NOT NULL,
  `stroke` int NOT NULL,
  `updated_by` varchar(32) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `unique_hole` (`name_club`,`gender`,`number_hole`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_club`
--

LOCK TABLES `course_club` WRITE;
/*!40000 ALTER TABLE `course_club` DISABLE KEYS */;
INSERT INTO `course_club` VALUES (1,'OVGC','M',1,'OutwardBound',4,7,'admin','2026-04-30 09:04:39'),(2,'OVGC','M',2,'Homestead',4,3,'admin','2026-04-30 09:04:39'),(3,'OVGC','M',3,'Hutt',3,13,'admin','2026-04-30 09:04:39'),(4,'OVGC','M',4,'Pines',3,9,'admin','2026-04-30 09:04:39'),(5,'OVGC','M',5,'Longreach',5,5,'admin','2026-04-30 09:04:39'),(6,'OVGC','M',6,'Panorama',4,1,'admin','2026-04-30 09:04:39'),(7,'OVGC','M',7,'Trap',3,11,'admin','2026-04-30 09:04:39'),(8,'OVGC','M',8,'Temptation',4,17,'admin','2026-04-30 09:04:39'),(9,'OVGC','M',9,'Roadside',3,15,'admin','2026-04-30 09:04:39'),(10,'OVGC','M',10,'OutwardBound',4,8,'admin','2026-04-30 09:04:39'),(11,'OVGC','M',11,'Homestead',4,4,'admin','2026-04-30 09:04:39'),(12,'OVGC','M',12,'Hutt',3,16,'admin','2026-04-30 09:04:39'),(13,'OVGC','M',13,'Pines',3,12,'admin','2026-04-30 09:04:39'),(14,'OVGC','M',14,'Longreach',5,6,'admin','2026-04-30 09:04:39'),(15,'OVGC','M',15,'Panorama',4,2,'admin','2026-04-30 09:04:39'),(16,'OVGC','M',16,'Hillside',3,10,'admin','2026-04-30 09:04:39'),(17,'OVGC','M',17,'Temptation',4,18,'admin','2026-04-30 09:04:39'),(18,'OVGC','M',18,'Roadside',3,14,'admin','2026-04-30 09:04:39'),(19,'OVGC','F',1,'OutwardBound',4,1,'admin','2026-04-30 09:09:39'),(20,'OVGC','F',2,'Homestead',4,7,'admin','2026-04-30 09:09:39'),(21,'OVGC','F',3,'Hutt',3,15,'admin','2026-04-30 09:09:39'),(22,'OVGC','F',4,'Pines',3,13,'admin','2026-04-30 09:09:39'),(23,'OVGC','F',5,'Longreach',5,5,'admin','2026-04-30 09:09:39'),(24,'OVGC','F',6,'Panorama',4,3,'admin','2026-04-30 09:09:39'),(25,'OVGC','F',7,'Trap',3,9,'admin','2026-04-30 09:09:39'),(26,'OVGC','F',8,'Temptation',4,11,'admin','2026-04-30 09:09:39'),(27,'OVGC','F',9,'Roadside',3,17,'admin','2026-04-30 09:09:39'),(28,'OVGC','F',10,'OutwardBound',4,2,'admin','2026-04-30 09:09:39'),(29,'OVGC','F',11,'Homestead',4,8,'admin','2026-04-30 09:09:39'),(30,'OVGC','F',12,'Hutt',3,14,'admin','2026-04-30 09:09:39'),(31,'OVGC','F',13,'Pines',3,18,'admin','2026-04-30 09:09:39'),(32,'OVGC','F',14,'Longreach',5,6,'admin','2026-04-30 09:09:39'),(33,'OVGC','F',15,'Panorama',4,4,'admin','2026-04-30 09:09:39'),(34,'OVGC','F',16,'Hillside',3,10,'admin','2026-04-30 09:09:39'),(35,'OVGC','F',17,'Temptation',4,12,'admin','2026-04-30 09:09:39'),(36,'OVGC','F',18,'Roadside',3,16,'admin','2026-04-30 09:09:39');
/*!40000 ALTER TABLE `course_club` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_played`
--

DROP TABLE IF EXISTS `course_played`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_played` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `name_course` varchar(16) NOT NULL,
  `name_club` varchar(16) NOT NULL,
  `ident_eclectic` varchar(16) NOT NULL,
  `updated_by` varchar(32) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `unique_course_played` (`name_club`,`name_course`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_played`
--

LOCK TABLES `course_played` WRITE;
/*!40000 ALTER TABLE `course_played` DISABLE KEYS */;
INSERT INTO `course_played` VALUES (1,'Whites','OVGC','Twilight','admin','2026-06-09 08:45:37'),(2,'Blues','OVGC','Twilight','admin','2026-06-09 08:45:21');
/*!40000 ALTER TABLE `course_played` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_played_hole`
--

DROP TABLE IF EXISTS `course_played_hole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_played_hole` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `course_played_id` int NOT NULL,
  `number_hole` int NOT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `unique_course_played_number_hole` (`course_played_id`,`number_hole`),
  CONSTRAINT `fk_course_played_hole_course_played` FOREIGN KEY (`course_played_id`) REFERENCES `course_played` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT=' ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_played_hole`
--

LOCK TABLES `course_played_hole` WRITE;
/*!40000 ALTER TABLE `course_played_hole` DISABLE KEYS */;
INSERT INTO `course_played_hole` VALUES (19,2,10,'admin','2026-06-09 08:45:21'),(20,2,11,'admin','2026-06-09 08:45:21'),(21,2,12,'admin','2026-06-09 08:45:21'),(22,2,13,'admin','2026-06-09 08:45:21'),(23,2,14,'admin','2026-06-09 08:45:21'),(24,2,15,'admin','2026-06-09 08:45:21'),(25,2,16,'admin','2026-06-09 08:45:21'),(26,2,17,'admin','2026-06-09 08:45:21'),(27,2,18,'admin','2026-06-09 08:45:21'),(28,1,1,'admin','2026-06-09 08:45:37'),(29,1,2,'admin','2026-06-09 08:45:37'),(30,1,3,'admin','2026-06-09 08:45:37'),(31,1,4,'admin','2026-06-09 08:45:37'),(32,1,5,'admin','2026-06-09 08:45:37'),(33,1,6,'admin','2026-06-09 08:45:37'),(34,1,7,'admin','2026-06-09 08:45:37'),(35,1,8,'admin','2026-06-09 08:45:37'),(36,1,9,'admin','2026-06-09 08:45:37');
/*!40000 ALTER TABLE `course_played_hole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `handicap_audit`
--

DROP TABLE IF EXISTS `handicap_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `handicap_audit` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `row_id_player` int NOT NULL,
  `handicap_previous` int NOT NULL COMMENT 'Handicap before this change',
  `handicap_new` int NOT NULL COMMENT 'Handicap after this change',
  `handicap_source` enum('card_scoring','admin_adjustment','system_import') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin_adjustment',
  `season_year` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Season the card was played, e.g. 25_26',
  `number_round` int DEFAULT NULL COMMENT 'Round number within that season',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Why was this changed (for admin adjustments)',
  `changed_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_player` (`row_id_player`),
  KEY `idx_source` (`handicap_source`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `handicap_audit_ibfk_1` FOREIGN KEY (`row_id_player`) REFERENCES `roster` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `handicap_audit`
--

LOCK TABLES `handicap_audit` WRITE;
/*!40000 ALTER TABLE `handicap_audit` DISABLE KEYS */;
INSERT INTO `handicap_audit` VALUES (3,10,0,17,'admin_adjustment',NULL,NULL,'player_created','scorer','2026-06-09 08:34:36','scorer','2026-06-09 08:34:36'),(4,11,0,16,'admin_adjustment',NULL,NULL,'player_created','scorer','2026-06-10 08:09:39','scorer','2026-06-10 08:09:39'),(5,1,6,8,'card_scoring','25_26',8,'finish_round_card_scoring_backfill','system','2026-06-10 08:44:49','system','2026-06-10 08:44:49'),(6,2,4,1,'card_scoring','25_26',8,'finish_round_card_scoring_backfill','system','2026-06-10 08:44:49','system','2026-06-10 08:44:49'),(8,4,5,4,'card_scoring','25_26',9,'finish_round_card_scoring','scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(9,11,16,22,'card_scoring','25_26',9,'finish_round_card_scoring','scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(10,1,8,0,'card_scoring','25_26',9,'finish_round_card_scoring','scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(11,1,0,16,'card_scoring','25_26',1034,'finish_round_card_scoring','smoke_bot','2026-06-11 08:44:06','smoke_bot','2026-06-11 08:44:06'),(12,10,8,7,'card_scoring','25_26',840,'finish_round_card_scoring','scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(13,12,0,13,'admin_adjustment',NULL,NULL,'player_created','scorer','2026-06-11 09:00:48','scorer','2026-06-11 09:00:48'),(14,13,0,27,'admin_adjustment',NULL,NULL,'player_created','scorer','2026-06-11 09:02:44','scorer','2026-06-11 09:02:44'),(15,11,22,16,'card_scoring','25_26',841,'finish_round_card_scoring','scorer','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(16,13,27,18,'card_scoring','25_26',841,'finish_round_card_scoring','scorer','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14');
/*!40000 ALTER TABLE `handicap_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roster`
--

DROP TABLE IF EXISTS `roster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roster` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `player_identifier` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `status` enum('active','scored','inactive') NOT NULL DEFAULT 'active',
  `handicap` int DEFAULT '0',
  `date_first_played` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `player_identifier` (`player_identifier`),
  UNIQUE KEY `alias` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roster`
--

LOCK TABLES `roster` WRITE;
/*!40000 ALTER TABLE `roster` DISABLE KEYS */;
INSERT INTO `roster` VALUES (1,'P1','Alice','Anderson','Alias_Alice','female','active',16,'2026-06-06','2026-06-06 21:44:24','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(2,'P2','Bob','Brown','BobbyBee','female','active',1,'2026-06-06','2026-06-06 21:44:24','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(3,'P3','Cara','Clark','C3','female','inactive',4,'2026-06-06','2026-06-06 21:44:24','2026-06-10 07:35:54','scorer','2026-06-10 07:35:54'),(4,'P4','Dan','Davis','D4','female','active',4,'2026-06-06','2026-06-06 21:44:24','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(10,'EdgarE','Edgar','Evans',NULL,'male','active',7,NULL,'2026-06-09 08:34:36','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(11,'HarveyW','Harvey','Wilson',NULL,'male','active',16,NULL,'2026-06-10 08:09:39','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(12,'HenryS','Henry','Sinkler','Fonzie','male','active',13,NULL,'2026-06-11 09:00:48','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14'),(13,'GaryN','Gary','Numan',NULL,'male','active',18,NULL,'2026-06-11 09:02:44','2026-06-11 09:04:14','scorer','2026-06-11 09:04:14');
/*!40000 ALTER TABLE `roster` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','scorer') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'admin','$2y$10$eMPk9K1PZmHRXeQF/R5SOe95NSMWxc7gWt9DhKSzD8CYJzN84Uvh2','System','Administrator','admin',1,'2026-05-07 08:17:03',NULL,NULL,'2026-06-11 08:54:40'),(2,'scorer','$2y$10$5oMwBLhgdZ6S8d2.YBBrJO5XmR1thVHzFvKDLWl8yDSr2ML0O7jcC','Score','Keeper','scorer',1,'2026-06-06 21:44:24',NULL,'admin','2026-06-09 08:06:26');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `TW4_live`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_live` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_live`;

--
-- Table structure for table `best_five`
--

DROP TABLE IF EXISTS `best_five`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `best_five` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT '0',
  `points_total` int NOT NULL DEFAULT '0',
  `points_best_1` int NOT NULL DEFAULT '0',
  `points_best_2` int NOT NULL DEFAULT '0',
  `points_best_3` int NOT NULL DEFAULT '0',
  `points_best_4` int NOT NULL DEFAULT '0',
  `points_best_5` int NOT NULL DEFAULT '0',
  `round_best_1` int NOT NULL DEFAULT '0',
  `round_best_2` int NOT NULL DEFAULT '0',
  `round_best_3` int NOT NULL DEFAULT '0',
  `round_best_4` int NOT NULL DEFAULT '0',
  `round_best_5` int NOT NULL DEFAULT '0',
  `points_movement` int NOT NULL DEFAULT '0',
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_best_five_season_player` (`season_year`,`row_id_player`),
  KEY `idx_best_five_player` (`row_id_player`),
  KEY `idx_best_five_season` (`season_year`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `best_five`
--

LOCK TABLES `best_five` WRITE;
/*!40000 ALTER TABLE `best_five` DISABLE KEYS */;
INSERT INTO `best_five` VALUES (12,'25_26',1,841,34,19,15,0,0,0,840,841,0,0,0,15,'scorer','2026-06-11 09:32:44'),(13,'25_26',2,841,33,18,15,0,0,0,840,841,0,0,0,15,'scorer','2026-06-11 09:32:44'),(14,'25_26',10,841,37,20,17,0,0,0,840,841,0,0,0,17,'scorer','2026-06-11 09:32:44'),(15,'25_26',11,841,43,26,17,0,0,0,841,840,0,0,0,26,'scorer','2026-06-11 09:32:44'),(16,'25_26',12,841,20,20,0,0,0,0,841,0,0,0,0,20,'scorer','2026-06-11 09:32:44'),(17,'25_26',13,841,29,29,0,0,0,0,841,0,0,0,0,29,'scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `best_five` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card`
--

DROP TABLE IF EXISTS `card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `row_id_player` int NOT NULL,
  `handicap_applied` int NOT NULL,
  `score` int NOT NULL,
  `points` int NOT NULL,
  `handicap_updated` int DEFAULT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_card_player` (`row_id_player`),
  KEY `idx_card_player` (`row_id_player`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card`
--

LOCK TABLES `card` WRITE;
/*!40000 ALTER TABLE `card` DISABLE KEYS */;
INSERT INTO `card` VALUES (54,1,16,44,15,16,'scorer','2026-06-11 09:00:00'),(55,12,13,38,20,13,'scorer','2026-06-11 09:01:35'),(56,11,22,36,26,16,'scorer','2026-06-11 09:04:14'),(57,2,1,37,15,1,'scorer','2026-06-11 09:02:24'),(58,13,27,36,29,18,'scorer','2026-06-11 09:04:14'),(59,10,7,38,17,7,'scorer','2026-06-11 09:04:03');
/*!40000 ALTER TABLE `card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_by_hole`
--

DROP TABLE IF EXISTS `card_by_hole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_by_hole` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `row_id_card` int NOT NULL,
  `hole` int NOT NULL,
  `score` int NOT NULL,
  `shots` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_card_hole` (`row_id_card`,`hole`),
  KEY `idx_card_by_hole_card` (`row_id_card`),
  CONSTRAINT `fk_card_by_hole_card` FOREIGN KEY (`row_id_card`) REFERENCES `card` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=523 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_by_hole`
--

LOCK TABLES `card_by_hole` WRITE;
/*!40000 ALTER TABLE `card_by_hole` DISABLE KEYS */;
INSERT INTO `card_by_hole` VALUES (469,54,1,6,1,1,'scorer','2026-06-11 09:00:00'),(470,54,2,5,1,2,'scorer','2026-06-11 09:00:00'),(471,54,3,4,1,2,'scorer','2026-06-11 09:00:00'),(472,54,4,6,1,0,'scorer','2026-06-11 09:00:00'),(473,54,5,3,1,5,'scorer','2026-06-11 09:00:00'),(474,54,6,5,1,2,'scorer','2026-06-11 09:00:00'),(475,54,7,6,1,0,'scorer','2026-06-11 09:00:00'),(476,54,8,5,1,2,'scorer','2026-06-11 09:00:00'),(477,54,9,4,0,1,'scorer','2026-06-11 09:00:00'),(478,55,1,6,1,1,'scorer','2026-06-11 09:01:35'),(479,55,2,6,1,1,'scorer','2026-06-11 09:01:35'),(480,55,3,5,1,1,'scorer','2026-06-11 09:01:35'),(481,55,4,3,1,3,'scorer','2026-06-11 09:01:35'),(482,55,5,3,1,5,'scorer','2026-06-11 09:01:35'),(483,55,6,4,1,3,'scorer','2026-06-11 09:01:35'),(484,55,7,2,1,4,'scorer','2026-06-11 09:01:35'),(485,55,8,5,0,1,'scorer','2026-06-11 09:01:35'),(486,55,9,4,0,1,'scorer','2026-06-11 09:01:35'),(487,56,1,3,1,4,'scorer','2026-06-11 09:01:50'),(488,56,2,5,2,3,'scorer','2026-06-11 09:01:50'),(489,56,3,4,1,2,'scorer','2026-06-11 09:01:50'),(490,56,4,3,1,3,'scorer','2026-06-11 09:01:50'),(491,56,5,5,1,3,'scorer','2026-06-11 09:01:50'),(492,56,6,4,2,4,'scorer','2026-06-11 09:01:50'),(493,56,7,3,1,3,'scorer','2026-06-11 09:01:50'),(494,56,8,5,1,2,'scorer','2026-06-11 09:01:50'),(495,56,9,4,1,2,'scorer','2026-06-11 09:01:50'),(496,57,1,6,1,1,'scorer','2026-06-11 09:02:24'),(497,57,2,6,0,0,'scorer','2026-06-11 09:02:24'),(498,57,3,3,0,2,'scorer','2026-06-11 09:02:24'),(499,57,4,3,0,2,'scorer','2026-06-11 09:02:24'),(500,57,5,5,0,2,'scorer','2026-06-11 09:02:24'),(501,57,6,4,0,2,'scorer','2026-06-11 09:02:24'),(502,57,7,4,0,1,'scorer','2026-06-11 09:02:24'),(503,57,8,3,0,3,'scorer','2026-06-11 09:02:24'),(504,57,9,3,0,2,'scorer','2026-06-11 09:02:24'),(505,58,1,5,2,3,'scorer','2026-06-11 09:03:32'),(506,58,2,4,2,4,'scorer','2026-06-11 09:03:32'),(507,58,3,2,1,4,'scorer','2026-06-11 09:03:32'),(508,58,4,5,2,2,'scorer','2026-06-11 09:03:32'),(509,58,5,4,2,5,'scorer','2026-06-11 09:03:32'),(510,58,6,3,2,5,'scorer','2026-06-11 09:03:32'),(511,58,7,5,1,1,'scorer','2026-06-11 09:03:32'),(512,58,8,4,1,3,'scorer','2026-06-11 09:03:32'),(513,58,9,4,1,2,'scorer','2026-06-11 09:03:32'),(514,59,1,5,1,2,'scorer','2026-06-11 09:04:03'),(515,59,2,5,1,2,'scorer','2026-06-11 09:04:03'),(516,59,3,3,0,2,'scorer','2026-06-11 09:04:03'),(517,59,4,5,0,0,'scorer','2026-06-11 09:04:03'),(518,59,5,4,1,4,'scorer','2026-06-11 09:04:03'),(519,59,6,3,1,4,'scorer','2026-06-11 09:04:03'),(520,59,7,5,0,0,'scorer','2026-06-11 09:04:03'),(521,59,8,5,0,1,'scorer','2026-06-11 09:04:03'),(522,59,9,3,0,2,'scorer','2026-06-11 09:04:03');
/*!40000 ALTER TABLE `card_by_hole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `type_result` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `number_result` int NOT NULL,
  `player_identifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value_result` int NOT NULL,
  `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_results_type_result` (`type_result`),
  KEY `idx_results_player_identifier` (`player_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
INSERT INTO `results` VALUES (68,'Place',1,'GaryN',18,'scorer','2026-06-11 09:32:40'),(69,'Place',2,'HarveyW',12,'scorer','2026-06-11 09:32:40'),(70,'Place',3,'HenryS',6,'scorer','2026-06-11 09:32:40'),(71,'C_P',1,'GaryN',1,'scorer','2026-06-11 09:32:40'),(72,'Twos',1,'GaryN',1,'scorer','2026-06-11 09:32:40'),(73,'Twos',1,'HenryS',1,'scorer','2026-06-11 09:32:40');
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `round` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) DEFAULT NULL,
  `number_round` int NOT NULL,
  `round_date` date DEFAULT NULL,
  `course_played_id` int DEFAULT NULL,
  `workflow_step` enum('not_started','card_entry_open','results_presented','finished','cancelled') NOT NULL DEFAULT 'not_started',
  `card_count` int NOT NULL DEFAULT '0',
  `results_presented_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `locked_by_staff_id` int DEFAULT NULL,
  `lock_acquired_at` timestamp NULL DEFAULT NULL,
  `lock_expires_at` timestamp NULL DEFAULT NULL,
  `lock_released_by_staff_id` int DEFAULT NULL,
  `lock_released_at` timestamp NULL DEFAULT NULL,
  `lock_release_reason` enum('logout','session_expired','admin_forced','finished') DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_round_season_number` (`season_year`,`number_round`),
  KEY `idx_round_workflow_step` (`workflow_step`),
  KEY `idx_round_locked_by_staff_id` (`locked_by_staff_id`),
  KEY `idx_round_lock_expires_at` (`lock_expires_at`),
  KEY `idx_round_course_played_id` (`course_played_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `round`
--

LOCK TABLES `round` WRITE;
/*!40000 ALTER TABLE `round` DISABLE KEYS */;
INSERT INTO `round` VALUES (2,'25_26',841,NULL,NULL,'not_started',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-11 09:32:44','finished','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `round` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `TW4_history`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_history` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_history`;

--
-- Table structure for table `best_five`
--

DROP TABLE IF EXISTS `best_five`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `best_five` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round_snapshot` int NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT '0',
  `points_total` int NOT NULL DEFAULT '0',
  `points_best_1` int NOT NULL DEFAULT '0',
  `points_best_2` int NOT NULL DEFAULT '0',
  `points_best_3` int NOT NULL DEFAULT '0',
  `points_best_4` int NOT NULL DEFAULT '0',
  `points_best_5` int NOT NULL DEFAULT '0',
  `round_best_1` int NOT NULL DEFAULT '0',
  `round_best_2` int NOT NULL DEFAULT '0',
  `round_best_3` int NOT NULL DEFAULT '0',
  `round_best_4` int NOT NULL DEFAULT '0',
  `round_best_5` int NOT NULL DEFAULT '0',
  `points_movement` int NOT NULL DEFAULT '0',
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_history_best_five_snapshot_player` (`season_year`,`number_round_snapshot`,`row_id_player`),
  KEY `idx_history_best_five_snapshot` (`season_year`,`number_round_snapshot`),
  KEY `idx_history_best_five_player` (`row_id_player`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `best_five`
--

LOCK TABLES `best_five` WRITE;
/*!40000 ALTER TABLE `best_five` DISABLE KEYS */;
INSERT INTO `best_five` VALUES (2,'25_26',840,1,840,19,19,0,0,0,0,840,0,0,0,0,19,'scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(3,'25_26',840,2,840,18,18,0,0,0,0,840,0,0,0,0,18,'scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(4,'25_26',840,10,840,20,20,0,0,0,0,840,0,0,0,0,20,'scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(5,'25_26',840,11,840,17,17,0,0,0,0,840,0,0,0,0,17,'scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(16,'25_26',841,1,841,34,19,15,0,0,0,840,841,0,0,0,15,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44'),(17,'25_26',841,2,841,33,18,15,0,0,0,840,841,0,0,0,15,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44'),(18,'25_26',841,10,841,37,20,17,0,0,0,840,841,0,0,0,17,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44'),(19,'25_26',841,11,841,43,26,17,0,0,0,841,840,0,0,0,26,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44'),(20,'25_26',841,12,841,20,20,0,0,0,0,841,0,0,0,0,20,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44'),(21,'25_26',841,13,841,29,29,0,0,0,0,841,0,0,0,0,29,'scorer','2026-06-11 09:32:44','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `best_five` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card`
--

DROP TABLE IF EXISTS `card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `number_round` int NOT NULL,
  `row_id_round` int NOT NULL,
  `row_id_player` int NOT NULL,
  `handicap_applied` int NOT NULL,
  `score` int NOT NULL,
  `points` int NOT NULL,
  `handicap_updated` int DEFAULT NULL,
  `updated_by` varchar(100) NOT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_history_card_player` (`season_year`,`number_round`,`row_id_player`),
  KEY `idx_history_card_round` (`row_id_round`),
  CONSTRAINT `fk_history_card_round` FOREIGN KEY (`row_id_round`) REFERENCES `round` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card`
--

LOCK TABLES `card` WRITE;
/*!40000 ALTER TABLE `card` DISABLE KEYS */;
INSERT INTO `card` VALUES (1,'25_26',1,1,1,12,36,27,5,'scorer','2026-06-06 21:47:52','scorer','2026-06-06 21:47:52'),(2,'25_26',1,1,2,15,36,27,8,'scorer','2026-06-06 21:47:52','scorer','2026-06-06 21:47:52'),(3,'25_26',1,1,3,18,36,27,11,'scorer','2026-06-06 21:47:52','scorer','2026-06-06 21:47:52'),(4,'25_26',1,1,4,20,36,29,11,'scorer','2026-06-06 21:47:52','scorer','2026-06-06 21:47:52'),(8,'25_26',2,2,1,5,35,24,0,'scorer','2026-06-09 08:12:59','scorer','2026-06-09 08:12:59'),(9,'25_26',2,2,2,8,39,23,5,'scorer','2026-06-09 08:12:59','scorer','2026-06-09 08:12:59'),(10,'25_26',2,2,3,11,34,29,2,'scorer','2026-06-09 08:12:59','scorer','2026-06-09 08:12:59'),(11,'25_26',2,2,4,11,39,24,7,'scorer','2026-06-09 08:12:59','scorer','2026-06-09 08:12:59'),(15,'25_26',3,3,4,7,45,16,7,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(16,'25_26',3,3,3,2,36,20,2,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(17,'25_26',3,3,1,0,35,19,0,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(18,'25_26',3,3,2,5,35,24,1,'scorer','2026-06-09 08:25:12','scorer','2026-06-09 08:25:12'),(22,'25_26',4,4,1,0,38,13,0,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(23,'25_26',4,4,10,17,41,19,17,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(24,'25_26',4,4,2,1,34,18,1,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(25,'25_26',4,4,4,7,41,14,7,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(26,'25_26',4,4,3,2,36,16,2,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(29,'25_26',5,5,1,0,45,8,4,'scorer','2026-06-09 09:00:57','scorer','2026-06-09 09:00:57'),(30,'25_26',5,5,2,1,39,13,3,'scorer','2026-06-09 09:00:57','scorer','2026-06-09 09:00:57'),(31,'25_26',5,5,10,17,42,18,17,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(32,'25_26',5,5,4,7,37,20,5,'scorer','2026-06-09 09:00:57','scorer','2026-06-09 09:00:57'),(33,'25_26',5,5,3,2,41,11,4,'scorer','2026-06-09 09:00:57','scorer','2026-06-09 09:00:57'),(36,'25_26',6,6,1,4,41,12,6,'scorer','2026-06-09 09:14:01','scorer','2026-06-09 09:14:01'),(37,'25_26',6,6,2,3,33,20,2,'scorer','2026-06-09 09:14:01','scorer','2026-06-09 09:14:01'),(38,'25_26',6,6,4,5,40,14,5,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(39,'25_26',6,6,10,17,38,22,15,'scorer','2026-06-09 09:14:01','scorer','2026-06-09 09:14:01'),(40,'25_26',6,6,3,4,39,14,4,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(43,'25_26',7,7,3,4,39,14,4,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(44,'25_26',7,7,2,2,42,12,4,'scorer','2026-06-09 09:21:45','scorer','2026-06-09 09:21:45'),(45,'25_26',7,7,10,15,32,27,8,'scorer','2026-06-09 09:21:45','scorer','2026-06-09 09:21:45'),(46,'25_26',7,7,1,6,41,14,6,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(50,'25_26',8,8,1,6,44,11,8,'scorer','2026-06-10 08:37:58','scorer','2026-06-10 08:37:58'),(51,'25_26',8,8,2,4,30,23,1,'scorer','2026-06-10 08:37:58','scorer','2026-06-10 08:37:58'),(52,'25_26',8,8,11,16,39,20,16,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(53,'25_26',8,8,10,8,36,19,8,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(57,'25_26',9,9,4,5,32,21,4,'scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(58,'25_26',9,9,11,16,54,5,22,'scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(59,'25_26',9,9,10,8,36,19,8,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(60,'25_26',9,9,1,8,27,28,0,'scorer','2026-06-10 08:47:25','scorer','2026-06-10 08:47:25'),(65,'25_26',840,11,2,1,34,18,1,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(66,'25_26',840,11,1,16,40,19,16,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(67,'25_26',840,11,11,22,45,17,22,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(68,'25_26',840,11,10,8,36,20,7,'scorer','2026-06-11 08:58:01','scorer','2026-06-11 08:58:01'),(79,'25_26',841,13,1,16,44,15,16,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(80,'25_26',841,13,12,13,38,20,13,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(81,'25_26',841,13,11,22,36,26,16,'scorer','2026-06-11 09:04:14','scorer','2026-06-11 09:32:44'),(82,'25_26',841,13,2,1,37,15,1,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(83,'25_26',841,13,13,27,36,29,18,'scorer','2026-06-11 09:04:14','scorer','2026-06-11 09:32:44'),(84,'25_26',841,13,10,7,38,17,7,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_by_hole`
--

DROP TABLE IF EXISTS `card_by_hole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_by_hole` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `number_round` int NOT NULL,
  `row_id_card` int NOT NULL,
  `hole` int NOT NULL,
  `score` int NOT NULL,
  `shots` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(100) NOT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_history_card_hole` (`season_year`,`number_round`,`row_id_card`,`hole`),
  KEY `idx_history_cbh_card` (`row_id_card`),
  CONSTRAINT `fk_history_cbh_card` FOREIGN KEY (`row_id_card`) REFERENCES `card` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=748 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_by_hole`
--

LOCK TABLES `card_by_hole` WRITE;
/*!40000 ALTER TABLE `card_by_hole` DISABLE KEYS */;
INSERT INTO `card_by_hole` VALUES (1,'25_26',1,1,1,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(2,'25_26',1,1,2,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(3,'25_26',1,1,3,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(4,'25_26',1,1,4,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(5,'25_26',1,1,5,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(6,'25_26',1,1,6,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(7,'25_26',1,1,7,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(8,'25_26',1,1,8,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(9,'25_26',1,1,9,4,1,3,'scorer','2026-06-06 21:46:46','scorer','2026-06-06 21:47:52'),(10,'25_26',1,2,1,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(11,'25_26',1,2,2,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(12,'25_26',1,2,3,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(13,'25_26',1,2,4,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(14,'25_26',1,2,5,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(15,'25_26',1,2,6,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(16,'25_26',1,2,7,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(17,'25_26',1,2,8,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(18,'25_26',1,2,9,4,1,3,'scorer','2026-06-06 21:47:13','scorer','2026-06-06 21:47:52'),(19,'25_26',1,3,1,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(20,'25_26',1,3,2,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(21,'25_26',1,3,3,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(22,'25_26',1,3,4,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(23,'25_26',1,3,5,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(24,'25_26',1,3,6,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(25,'25_26',1,3,7,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(26,'25_26',1,3,8,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(27,'25_26',1,3,9,4,1,3,'scorer','2026-06-06 21:47:23','scorer','2026-06-06 21:47:52'),(28,'25_26',1,4,1,4,2,4,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(29,'25_26',1,4,2,4,2,4,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(30,'25_26',1,4,3,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(31,'25_26',1,4,4,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(32,'25_26',1,4,5,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(33,'25_26',1,4,6,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(34,'25_26',1,4,7,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(35,'25_26',1,4,8,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(36,'25_26',1,4,9,4,1,3,'scorer','2026-06-06 21:47:24','scorer','2026-06-06 21:47:52'),(64,'25_26',2,8,1,4,1,3,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(65,'25_26',2,8,2,4,1,3,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(66,'25_26',2,8,3,3,1,4,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(67,'25_26',2,8,4,3,1,4,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(68,'25_26',2,8,5,5,1,2,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(69,'25_26',2,8,6,6,0,0,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(70,'25_26',2,8,7,4,0,2,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(71,'25_26',2,8,8,3,0,3,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(72,'25_26',2,8,9,3,0,3,'scorer','2026-06-09 08:10:43','scorer','2026-06-09 08:12:59'),(73,'25_26',2,9,1,5,1,2,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(74,'25_26',2,9,2,5,1,2,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(75,'25_26',2,9,3,4,1,3,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(76,'25_26',2,9,4,4,1,3,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(77,'25_26',2,9,5,5,1,2,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(78,'25_26',2,9,6,5,1,2,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(79,'25_26',2,9,7,5,1,2,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(80,'25_26',2,9,8,3,1,4,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(81,'25_26',2,9,9,3,0,3,'scorer','2026-06-09 08:11:07','scorer','2026-06-09 08:12:59'),(82,'25_26',2,10,1,5,1,2,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(83,'25_26',2,10,2,5,1,2,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(84,'25_26',2,10,3,3,1,4,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(85,'25_26',2,10,4,3,1,4,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(86,'25_26',2,10,5,5,1,2,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(87,'25_26',2,10,6,4,1,3,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(88,'25_26',2,10,7,3,1,4,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(89,'25_26',2,10,8,3,1,4,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(90,'25_26',2,10,9,3,1,4,'scorer','2026-06-09 08:11:38','scorer','2026-06-09 08:12:59'),(91,'25_26',2,11,1,4,1,3,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(92,'25_26',2,11,2,4,1,3,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(93,'25_26',2,11,3,5,1,2,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(94,'25_26',2,11,4,3,1,4,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(95,'25_26',2,11,5,3,1,4,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(96,'25_26',2,11,6,5,1,2,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(97,'25_26',2,11,7,5,1,2,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(98,'25_26',2,11,8,5,1,2,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(99,'25_26',2,11,9,5,1,2,'scorer','2026-06-09 08:12:03','scorer','2026-06-09 08:12:59'),(127,'25_26',3,17,1,4,0,2,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(128,'25_26',3,17,2,4,0,2,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(129,'25_26',3,17,3,2,0,4,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(130,'25_26',3,17,4,5,0,1,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(131,'25_26',3,17,5,5,0,1,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(132,'25_26',3,17,6,6,0,0,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(133,'25_26',3,17,7,3,0,3,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(134,'25_26',3,17,8,3,0,3,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(135,'25_26',3,17,9,3,0,3,'scorer','2026-06-09 08:24:50','scorer','2026-06-09 08:25:12'),(136,'25_26',3,18,1,4,1,3,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(137,'25_26',3,18,2,5,1,2,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(138,'25_26',3,18,3,3,1,4,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(139,'25_26',3,18,4,4,1,3,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(140,'25_26',3,18,5,5,1,2,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(141,'25_26',3,18,6,3,0,3,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(142,'25_26',3,18,7,4,0,2,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(143,'25_26',3,18,8,5,0,1,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(144,'25_26',3,18,9,2,0,4,'scorer','2026-06-09 08:25:00','scorer','2026-06-09 08:25:12'),(145,'25_26',3,16,1,3,1,4,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(146,'25_26',3,16,2,3,1,4,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(147,'25_26',3,16,3,3,0,3,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(148,'25_26',3,16,4,5,0,1,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(149,'25_26',3,16,5,5,0,1,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(150,'25_26',3,16,6,5,0,1,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(151,'25_26',3,16,7,4,0,2,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(152,'25_26',3,16,8,4,0,2,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(153,'25_26',3,16,9,4,0,2,'scorer','2026-06-09 08:24:40','scorer','2026-06-09 08:25:12'),(154,'25_26',3,15,1,6,1,1,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(155,'25_26',3,15,2,6,1,1,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(156,'25_26',3,15,3,6,1,1,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(157,'25_26',3,15,4,5,1,2,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(158,'25_26',3,15,5,5,1,2,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(159,'25_26',3,15,6,5,1,2,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(160,'25_26',3,15,7,4,1,3,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(161,'25_26',3,15,8,4,0,2,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(162,'25_26',3,15,9,4,0,2,'scorer','2026-06-09 08:24:30','scorer','2026-06-09 08:25:12'),(190,'25_26',4,22,1,4,0,2,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(191,'25_26',4,22,2,4,0,2,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(192,'25_26',4,22,3,5,0,0,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(193,'25_26',4,22,4,5,0,0,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(194,'25_26',4,22,5,4,0,3,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(195,'25_26',4,22,6,5,0,1,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(196,'25_26',4,22,7,5,0,0,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(197,'25_26',4,22,8,3,0,3,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(198,'25_26',4,22,9,3,0,2,'scorer','2026-06-09 08:46:06','scorer','2026-06-09 08:48:33'),(199,'25_26',4,24,1,4,1,3,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(200,'25_26',4,24,2,4,0,2,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(201,'25_26',4,24,3,3,0,2,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(202,'25_26',4,24,4,3,0,2,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(203,'25_26',4,24,5,5,0,2,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(204,'25_26',4,24,6,5,0,1,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(205,'25_26',4,24,7,4,0,1,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(206,'25_26',4,24,8,4,0,2,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(207,'25_26',4,24,9,2,0,3,'scorer','2026-06-09 08:46:45','scorer','2026-06-09 08:48:33'),(208,'25_26',4,26,1,4,1,3,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(209,'25_26',4,26,2,4,0,2,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(210,'25_26',4,26,3,4,0,1,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(211,'25_26',4,26,4,5,0,0,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(212,'25_26',4,26,5,5,0,2,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(213,'25_26',4,26,6,5,0,1,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(214,'25_26',4,26,7,3,0,2,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(215,'25_26',4,26,8,3,0,3,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(216,'25_26',4,26,9,3,0,2,'scorer','2026-06-09 08:48:16','scorer','2026-06-09 08:48:33'),(217,'25_26',4,25,1,5,1,2,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(218,'25_26',4,25,2,4,1,3,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(219,'25_26',4,25,3,5,0,0,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(220,'25_26',4,25,4,4,0,1,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(221,'25_26',4,25,5,5,1,3,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(222,'25_26',4,25,6,4,1,3,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(223,'25_26',4,25,7,5,0,0,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(224,'25_26',4,25,8,4,0,2,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(225,'25_26',4,25,9,5,0,0,'scorer','2026-06-09 08:47:05','scorer','2026-06-09 08:48:33'),(226,'25_26',4,23,1,5,1,2,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(227,'25_26',4,23,2,4,1,3,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(228,'25_26',4,23,3,5,1,1,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(229,'25_26',4,23,4,4,1,2,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(230,'25_26',4,23,5,5,1,3,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(231,'25_26',4,23,6,4,1,3,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(232,'25_26',4,23,7,5,1,1,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(233,'25_26',4,23,8,4,1,3,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(234,'25_26',4,23,9,5,1,1,'scorer','2026-06-09 08:46:22','scorer','2026-06-09 08:48:33'),(253,'25_26',5,29,1,4,0,2,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(254,'25_26',5,29,2,4,0,2,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(255,'25_26',5,29,3,4,0,1,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(256,'25_26',5,29,4,5,0,0,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(257,'25_26',5,29,5,5,0,2,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(258,'25_26',5,29,6,5,0,1,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(259,'25_26',5,29,7,6,0,0,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(260,'25_26',5,29,8,6,0,0,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(261,'25_26',5,29,9,6,0,0,'scorer','2026-06-09 08:52:13','scorer','2026-06-09 09:00:57'),(262,'25_26',5,30,1,4,1,3,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(263,'25_26',5,30,2,4,0,2,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(264,'25_26',5,30,3,4,0,1,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(265,'25_26',5,30,4,4,0,1,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(266,'25_26',5,30,5,5,0,2,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(267,'25_26',5,30,6,6,0,0,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(268,'25_26',5,30,7,4,0,1,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(269,'25_26',5,30,8,4,0,2,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(270,'25_26',5,30,9,4,0,1,'scorer','2026-06-09 08:54:51','scorer','2026-06-09 09:00:57'),(271,'25_26',5,33,1,5,1,2,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(272,'25_26',5,33,2,4,0,2,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(273,'25_26',5,33,3,5,0,0,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(274,'25_26',5,33,4,4,0,1,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(275,'25_26',5,33,5,5,0,2,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(276,'25_26',5,33,6,4,0,2,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(277,'25_26',5,33,7,5,0,0,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(278,'25_26',5,33,8,4,0,2,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(279,'25_26',5,33,9,5,0,0,'scorer','2026-06-09 08:58:42','scorer','2026-06-09 09:00:57'),(280,'25_26',5,32,1,5,1,2,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(281,'25_26',5,32,2,5,1,2,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(282,'25_26',5,32,3,6,0,0,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(283,'25_26',5,32,4,6,0,0,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(284,'25_26',5,32,5,3,1,5,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(285,'25_26',5,32,6,3,1,4,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(286,'25_26',5,32,7,3,0,2,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(287,'25_26',5,32,8,3,0,3,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(288,'25_26',5,32,9,3,0,2,'scorer','2026-06-09 08:58:20','scorer','2026-06-09 09:00:57'),(289,'25_26',5,31,1,6,1,1,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(290,'25_26',5,31,2,5,1,2,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(291,'25_26',5,31,3,3,1,3,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(292,'25_26',5,31,4,6,1,0,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(293,'25_26',5,31,5,5,1,3,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(294,'25_26',5,31,6,4,1,3,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(295,'25_26',5,31,7,6,1,0,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(296,'25_26',5,31,8,5,1,2,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(297,'25_26',5,31,9,2,1,4,'scorer','2026-06-09 08:55:03','scorer','2026-06-09 09:00:57'),(316,'25_26',6,36,1,4,1,3,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(317,'25_26',6,36,2,4,0,2,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(318,'25_26',6,36,3,4,0,1,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(319,'25_26',6,36,4,4,0,1,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(320,'25_26',6,36,5,5,0,2,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(321,'25_26',6,36,6,5,1,2,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(322,'25_26',6,36,7,5,0,0,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(323,'25_26',6,36,8,5,0,1,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(324,'25_26',6,36,9,5,0,0,'scorer','2026-06-09 09:11:30','scorer','2026-06-09 09:14:01'),(325,'25_26',6,37,1,4,1,3,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(326,'25_26',6,37,2,4,0,2,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(327,'25_26',6,37,3,4,0,1,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(328,'25_26',6,37,4,4,0,1,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(329,'25_26',6,37,5,3,0,4,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(330,'25_26',6,37,6,3,1,4,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(331,'25_26',6,37,7,3,0,2,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(332,'25_26',6,37,8,3,0,3,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(333,'25_26',6,37,9,5,0,0,'scorer','2026-06-09 09:11:44','scorer','2026-06-09 09:14:01'),(334,'25_26',6,40,1,4,1,3,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(335,'25_26',6,40,2,4,0,2,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(336,'25_26',6,40,3,3,0,2,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(337,'25_26',6,40,4,3,0,2,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(338,'25_26',6,40,5,5,0,2,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(339,'25_26',6,40,6,5,1,2,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(340,'25_26',6,40,7,5,0,0,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(341,'25_26',6,40,8,5,0,1,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(342,'25_26',6,40,9,5,0,0,'scorer','2026-06-09 09:13:38','scorer','2026-06-09 09:14:01'),(343,'25_26',6,38,1,5,1,2,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(344,'25_26',6,38,2,4,0,2,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(345,'25_26',6,38,3,5,0,0,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(346,'25_26',6,38,4,4,0,1,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(347,'25_26',6,38,5,5,1,3,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(348,'25_26',6,38,6,4,1,3,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(349,'25_26',6,38,7,5,0,0,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(350,'25_26',6,38,8,5,0,1,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(351,'25_26',6,38,9,3,0,2,'scorer','2026-06-09 09:11:57','scorer','2026-06-09 09:14:01'),(352,'25_26',6,39,1,2,1,5,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(353,'25_26',6,39,2,5,1,2,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(354,'25_26',6,39,3,4,1,2,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(355,'25_26',6,39,4,3,1,3,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(356,'25_26',6,39,5,5,1,3,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(357,'25_26',6,39,6,5,1,2,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(358,'25_26',6,39,7,4,1,2,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(359,'25_26',6,39,8,5,1,2,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(360,'25_26',6,39,9,5,1,1,'scorer','2026-06-09 09:12:08','scorer','2026-06-09 09:14:01'),(379,'25_26',7,46,1,2,1,5,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(380,'25_26',7,46,2,5,0,1,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(381,'25_26',7,46,3,3,0,2,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(382,'25_26',7,46,4,5,0,0,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(383,'25_26',7,46,5,6,1,2,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(384,'25_26',7,46,6,5,1,2,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(385,'25_26',7,46,7,4,0,1,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(386,'25_26',7,46,8,5,0,1,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(387,'25_26',7,46,9,6,0,0,'scorer','2026-06-09 09:20:58','scorer','2026-06-09 09:21:45'),(388,'25_26',7,44,1,6,1,1,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(389,'25_26',7,44,2,5,0,1,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(390,'25_26',7,44,3,4,0,1,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(391,'25_26',7,44,4,6,0,0,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(392,'25_26',7,44,5,5,0,2,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(393,'25_26',7,44,6,3,0,3,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(394,'25_26',7,44,7,6,0,0,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(395,'25_26',7,44,8,5,0,1,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(396,'25_26',7,44,9,2,0,3,'scorer','2026-06-09 09:19:42','scorer','2026-06-09 09:21:45'),(397,'25_26',7,43,1,4,1,3,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(398,'25_26',7,43,2,4,0,2,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(399,'25_26',7,43,3,5,0,0,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(400,'25_26',7,43,4,4,0,1,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(401,'25_26',7,43,5,4,0,3,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(402,'25_26',7,43,6,5,1,2,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(403,'25_26',7,43,7,4,0,1,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(404,'25_26',7,43,8,4,0,2,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(405,'25_26',7,43,9,5,0,0,'scorer','2026-06-09 09:19:32','scorer','2026-06-09 09:21:45'),(406,'25_26',7,45,1,5,1,2,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(407,'25_26',7,45,2,4,1,3,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(408,'25_26',7,45,3,5,1,1,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(409,'25_26',7,45,4,4,1,2,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(410,'25_26',7,45,5,5,1,3,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(411,'25_26',7,45,6,3,1,4,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(412,'25_26',7,45,7,2,1,4,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(413,'25_26',7,45,8,2,0,4,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(414,'25_26',7,45,9,2,1,4,'scorer','2026-06-09 09:20:27','scorer','2026-06-09 09:21:45'),(442,'25_26',8,50,1,6,1,1,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(443,'25_26',8,50,2,5,0,1,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(444,'25_26',8,50,3,4,0,1,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(445,'25_26',8,50,4,6,0,0,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(446,'25_26',8,50,5,5,1,3,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(447,'25_26',8,50,6,3,1,4,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(448,'25_26',8,50,7,5,0,0,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(449,'25_26',8,50,8,5,0,1,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(450,'25_26',8,50,9,5,0,0,'scorer','2026-06-10 08:35:39','scorer','2026-06-10 08:37:58'),(451,'25_26',8,51,1,3,1,4,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(452,'25_26',8,51,2,3,0,3,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(453,'25_26',8,51,3,3,0,2,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(454,'25_26',8,51,4,3,0,2,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(455,'25_26',8,51,5,5,0,2,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(456,'25_26',8,51,6,4,1,3,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(457,'25_26',8,51,7,3,0,2,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(458,'25_26',8,51,8,4,0,2,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(459,'25_26',8,51,9,2,0,3,'scorer','2026-06-10 08:36:16','scorer','2026-06-10 08:37:58'),(460,'25_26',8,53,1,4,1,3,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(461,'25_26',8,53,2,4,1,3,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(462,'25_26',8,53,3,3,0,2,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(463,'25_26',8,53,4,3,0,2,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(464,'25_26',8,53,5,5,1,3,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(465,'25_26',8,53,6,6,1,1,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(466,'25_26',8,53,7,4,0,1,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(467,'25_26',8,53,8,3,0,3,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(468,'25_26',8,53,9,4,0,1,'scorer','2026-06-10 08:37:30','scorer','2026-06-10 08:37:58'),(469,'25_26',8,52,1,5,1,2,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(470,'25_26',8,52,2,5,1,2,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(471,'25_26',8,52,3,4,1,2,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(472,'25_26',8,52,4,4,1,2,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(473,'25_26',8,52,5,5,1,3,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(474,'25_26',8,52,6,4,1,3,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(475,'25_26',8,52,7,3,1,3,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(476,'25_26',8,52,8,5,0,1,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(477,'25_26',8,52,9,4,1,2,'scorer','2026-06-10 08:36:47','scorer','2026-06-10 08:37:58'),(505,'25_26',9,60,1,3,1,4,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(506,'25_26',9,60,2,3,1,4,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(507,'25_26',9,60,3,3,0,2,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(508,'25_26',9,60,4,3,0,2,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(509,'25_26',9,60,5,3,1,5,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(510,'25_26',9,60,6,3,1,4,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(511,'25_26',9,60,7,3,0,2,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(512,'25_26',9,60,8,3,0,3,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(513,'25_26',9,60,9,3,0,2,'scorer','2026-06-10 08:47:11','scorer','2026-06-10 08:47:25'),(514,'25_26',9,57,1,4,1,3,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(515,'25_26',9,57,2,4,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(516,'25_26',9,57,3,3,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(517,'25_26',9,57,4,3,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(518,'25_26',9,57,5,5,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(519,'25_26',9,57,6,4,1,3,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(520,'25_26',9,57,7,3,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(521,'25_26',9,57,8,4,0,2,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(522,'25_26',9,57,9,2,0,3,'scorer','2026-06-10 08:46:28','scorer','2026-06-10 08:47:25'),(523,'25_26',9,59,1,4,1,3,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(524,'25_26',9,59,2,4,1,3,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(525,'25_26',9,59,3,4,0,1,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(526,'25_26',9,59,4,4,0,1,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(527,'25_26',9,59,5,4,1,4,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(528,'25_26',9,59,6,4,1,3,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(529,'25_26',9,59,7,4,0,1,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(530,'25_26',9,59,8,4,0,2,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(531,'25_26',9,59,9,4,0,1,'scorer','2026-06-10 08:46:57','scorer','2026-06-10 08:47:25'),(532,'25_26',9,58,1,6,1,1,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(533,'25_26',9,58,2,6,1,1,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(534,'25_26',9,58,3,6,1,0,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(535,'25_26',9,58,4,6,1,0,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(536,'25_26',9,58,5,6,1,2,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(537,'25_26',9,58,6,6,1,1,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(538,'25_26',9,58,7,6,1,0,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(539,'25_26',9,58,8,6,0,0,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(540,'25_26',9,58,9,6,1,0,'scorer','2026-06-10 08:46:45','scorer','2026-06-10 08:47:25'),(568,'25_26',840,66,1,5,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(569,'25_26',840,66,2,5,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(570,'25_26',840,66,3,4,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(571,'25_26',840,66,4,4,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(572,'25_26',840,66,5,6,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(573,'25_26',840,66,6,5,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(574,'25_26',840,66,7,4,1,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(575,'25_26',840,66,8,4,1,3,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(576,'25_26',840,66,9,3,0,2,'scorer','2026-06-11 08:56:49','scorer','2026-06-11 08:58:01'),(577,'25_26',840,65,1,4,1,3,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(578,'25_26',840,65,2,4,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(579,'25_26',840,65,3,4,0,1,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(580,'25_26',840,65,4,3,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(581,'25_26',840,65,5,5,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(582,'25_26',840,65,6,4,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(583,'25_26',840,65,7,3,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(584,'25_26',840,65,8,4,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(585,'25_26',840,65,9,3,0,2,'scorer','2026-06-11 08:56:21','scorer','2026-06-11 08:58:01'),(586,'25_26',840,68,1,4,1,3,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(587,'25_26',840,68,2,4,1,3,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(588,'25_26',840,68,3,4,0,1,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(589,'25_26',840,68,4,6,0,0,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(590,'25_26',840,68,5,5,1,3,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(591,'25_26',840,68,6,4,1,3,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(592,'25_26',840,68,7,3,0,2,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(593,'25_26',840,68,8,3,0,3,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(594,'25_26',840,68,9,3,0,2,'scorer','2026-06-11 08:57:40','scorer','2026-06-11 08:58:01'),(595,'25_26',840,67,1,6,1,1,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(596,'25_26',840,67,2,5,2,3,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(597,'25_26',840,67,3,4,1,2,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(598,'25_26',840,67,4,6,1,0,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(599,'25_26',840,67,5,5,1,3,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(600,'25_26',840,67,6,4,2,4,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(601,'25_26',840,67,7,6,1,0,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(602,'25_26',840,67,8,5,1,2,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(603,'25_26',840,67,9,4,1,2,'scorer','2026-06-11 08:57:19','scorer','2026-06-11 08:58:01'),(694,'25_26',841,79,1,6,1,1,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(695,'25_26',841,79,2,5,1,2,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(696,'25_26',841,79,3,4,1,2,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(697,'25_26',841,79,4,6,1,0,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(698,'25_26',841,79,5,3,1,5,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(699,'25_26',841,79,6,5,1,2,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(700,'25_26',841,79,7,6,1,0,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(701,'25_26',841,79,8,5,1,2,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(702,'25_26',841,79,9,4,0,1,'scorer','2026-06-11 09:00:00','scorer','2026-06-11 09:32:44'),(703,'25_26',841,82,1,6,1,1,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(704,'25_26',841,82,2,6,0,0,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(705,'25_26',841,82,3,3,0,2,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(706,'25_26',841,82,4,3,0,2,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(707,'25_26',841,82,5,5,0,2,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(708,'25_26',841,82,6,4,0,2,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(709,'25_26',841,82,7,4,0,1,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(710,'25_26',841,82,8,3,0,3,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(711,'25_26',841,82,9,3,0,2,'scorer','2026-06-11 09:02:24','scorer','2026-06-11 09:32:44'),(712,'25_26',841,84,1,5,1,2,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(713,'25_26',841,84,2,5,1,2,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(714,'25_26',841,84,3,3,0,2,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(715,'25_26',841,84,4,5,0,0,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(716,'25_26',841,84,5,4,1,4,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(717,'25_26',841,84,6,3,1,4,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(718,'25_26',841,84,7,5,0,0,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(719,'25_26',841,84,8,5,0,1,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(720,'25_26',841,84,9,3,0,2,'scorer','2026-06-11 09:04:03','scorer','2026-06-11 09:32:44'),(721,'25_26',841,81,1,3,1,4,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(722,'25_26',841,81,2,5,2,3,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(723,'25_26',841,81,3,4,1,2,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(724,'25_26',841,81,4,3,1,3,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(725,'25_26',841,81,5,5,1,3,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(726,'25_26',841,81,6,4,2,4,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(727,'25_26',841,81,7,3,1,3,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(728,'25_26',841,81,8,5,1,2,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(729,'25_26',841,81,9,4,1,2,'scorer','2026-06-11 09:01:50','scorer','2026-06-11 09:32:44'),(730,'25_26',841,80,1,6,1,1,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(731,'25_26',841,80,2,6,1,1,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(732,'25_26',841,80,3,5,1,1,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(733,'25_26',841,80,4,3,1,3,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(734,'25_26',841,80,5,3,1,5,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(735,'25_26',841,80,6,4,1,3,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(736,'25_26',841,80,7,2,1,4,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(737,'25_26',841,80,8,5,0,1,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(738,'25_26',841,80,9,4,0,1,'scorer','2026-06-11 09:01:35','scorer','2026-06-11 09:32:44'),(739,'25_26',841,83,1,5,2,3,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(740,'25_26',841,83,2,4,2,4,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(741,'25_26',841,83,3,2,1,4,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(742,'25_26',841,83,4,5,2,2,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(743,'25_26',841,83,5,4,2,5,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(744,'25_26',841,83,6,3,2,5,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(745,'25_26',841,83,7,5,1,1,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(746,'25_26',841,83,8,4,1,3,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44'),(747,'25_26',841,83,9,4,1,2,'scorer','2026-06-11 09:03:32','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `card_by_hole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `number_round` int NOT NULL,
  `type_result` varchar(16) NOT NULL,
  `number_result` int NOT NULL,
  `player_identifier` varchar(50) NOT NULL,
  `value_result` int NOT NULL,
  `updated_by` varchar(100) NOT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_history_results_round` (`season_year`,`number_round`),
  KEY `idx_history_results_type` (`type_result`),
  KEY `idx_history_results_player` (`player_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
INSERT INTO `results` VALUES (1,'25_26',1,'Place',1,'P4',12,'scorer','2026-06-06 21:47:35','scorer','2026-06-06 21:47:52'),(2,'25_26',1,'Place',2,'P3',8,'scorer','2026-06-06 21:47:35','scorer','2026-06-06 21:47:52'),(3,'25_26',1,'Place',3,'P1',4,'scorer','2026-06-06 21:47:35','scorer','2026-06-06 21:47:52'),(4,'25_26',1,'C_P',1,'not taker',1,'scorer','2026-06-06 21:47:35','scorer','2026-06-06 21:47:52'),(8,'25_26',2,'Place',1,'P3',12,'scorer','2026-06-09 08:12:51','scorer','2026-06-09 08:12:59'),(9,'25_26',2,'Place',2,'P1',8,'scorer','2026-06-09 08:12:51','scorer','2026-06-09 08:12:59'),(10,'25_26',2,'Place',3,'P4',4,'scorer','2026-06-09 08:12:51','scorer','2026-06-09 08:12:59'),(11,'25_26',2,'C_P',1,'P4',1,'scorer','2026-06-09 08:12:51','scorer','2026-06-09 08:12:59'),(15,'25_26',3,'Place',1,'P2',12,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(16,'25_26',3,'Place',2,'P3',8,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(17,'25_26',3,'Place',3,'P1',4,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(18,'25_26',3,'C_P',1,'P2',1,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(19,'25_26',3,'Twos',1,'P2',1,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(20,'25_26',3,'Twos',1,'P1',1,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(22,'25_26',4,'Place',1,'EdgarE',15,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(23,'25_26',4,'Place',2,'P2',10,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(24,'25_26',4,'Place',3,'P3',5,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(25,'25_26',4,'C_P',1,'EdgarE',1,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(26,'25_26',4,'Twos',1,'P2',1,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(29,'25_26',5,'Place',1,'P4',15,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(30,'25_26',5,'Place',2,'EdgarE',10,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(31,'25_26',5,'Place',3,'P2',5,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(32,'25_26',5,'C_P',1,'EdgarE',1,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(33,'25_26',5,'Twos',1,'EdgarE',1,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(36,'25_26',6,'Place',1,'EdgarE',15,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(37,'25_26',6,'Place',2,'P2',10,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(38,'25_26',6,'Place',3,'P4',5,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(39,'25_26',6,'C_P',1,'P1',1,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(40,'25_26',6,'Twos',1,'EdgarE',1,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(43,'25_26',7,'Place',1,'EdgarE',12,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(44,'25_26',7,'Place',2,'P3',8,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(45,'25_26',7,'Place',3,'P1',4,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(46,'25_26',7,'C_P',1,'P1',1,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(47,'25_26',7,'Twos',1,'EdgarE',3,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(48,'25_26',7,'Twos',1,'P1',1,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(49,'25_26',7,'Twos',1,'P2',1,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(50,'25_26',8,'Place',1,'P2',12,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(51,'25_26',8,'Place',2,'HarveyW',8,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(52,'25_26',8,'Place',3,'EdgarE',4,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(53,'25_26',8,'C_P',1,'P1',1,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(54,'25_26',8,'Twos',1,'P2',1,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(57,'25_26',9,'Place',1,'P1',12,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(58,'25_26',9,'Place',2,'P4',8,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(59,'25_26',9,'Place',3,'EdgarE',4,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(60,'25_26',9,'C_P',1,'not taker',1,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(61,'25_26',9,'Twos',1,'P4',1,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(64,'25_26',840,'Place',1,'EdgarE',12,'scorer','2026-06-11 08:57:57','scorer','2026-06-11 08:58:01'),(65,'25_26',840,'Place',2,'P1',8,'scorer','2026-06-11 08:57:57','scorer','2026-06-11 08:58:01'),(66,'25_26',840,'Place',3,'P2',4,'scorer','2026-06-11 08:57:57','scorer','2026-06-11 08:58:01'),(67,'25_26',840,'C_P',1,'not taker',1,'scorer','2026-06-11 08:57:57','scorer','2026-06-11 08:58:01'),(78,'25_26',841,'Place',1,'GaryN',18,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44'),(79,'25_26',841,'Place',2,'HarveyW',12,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44'),(80,'25_26',841,'Place',3,'HenryS',6,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44'),(81,'25_26',841,'C_P',1,'GaryN',1,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44'),(82,'25_26',841,'Twos',1,'GaryN',1,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44'),(83,'25_26',841,'Twos',1,'HenryS',1,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `round` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `number_round` int NOT NULL,
  `round_date` date DEFAULT NULL,
  `course_played_id` int DEFAULT NULL,
  `card_count` int NOT NULL DEFAULT '0',
  `results_presented_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_history_round_business` (`season_year`,`number_round`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `round`
--

LOCK TABLES `round` WRITE;
/*!40000 ALTER TABLE `round` DISABLE KEYS */;
INSERT INTO `round` VALUES (1,'25_26',1,'2026-06-06',1,4,'2026-06-06 21:47:35',NULL,'scorer','2026-06-06 21:47:35','scorer','2026-06-06 21:47:52'),(2,'25_26',2,'2026-06-09',1,4,'2026-06-09 08:12:51',NULL,'scorer','2026-06-09 08:12:51','scorer','2026-06-09 08:12:59'),(3,'25_26',3,'2026-06-09',1,4,'2026-06-09 08:25:07',NULL,'scorer','2026-06-09 08:25:07','scorer','2026-06-09 08:25:12'),(4,'25_26',4,'2026-06-09',1,5,'2026-06-09 08:48:28',NULL,'scorer','2026-06-09 08:48:28','scorer','2026-06-09 08:48:33'),(5,'25_26',5,'2026-06-09',1,5,'2026-06-09 09:00:52',NULL,'scorer','2026-06-09 09:00:52','scorer','2026-06-09 09:00:57'),(6,'25_26',6,'2026-06-09',1,5,'2026-06-09 09:13:45',NULL,'scorer','2026-06-09 09:13:45','scorer','2026-06-09 09:14:01'),(7,'25_26',7,'2026-06-09',1,4,'2026-06-09 09:21:21',NULL,'scorer','2026-06-09 09:21:21','scorer','2026-06-09 09:21:45'),(8,'25_26',8,'2026-06-10',2,4,'2026-06-10 08:37:41',NULL,'scorer','2026-06-10 08:37:41','scorer','2026-06-10 08:37:58'),(9,'25_26',9,'2026-06-10',2,4,'2026-06-10 08:47:20',NULL,'scorer','2026-06-10 08:47:20','scorer','2026-06-10 08:47:25'),(11,'25_26',840,'2026-06-11',1,4,'2026-06-11 08:57:57',NULL,'scorer','2026-06-11 08:57:57','scorer','2026-06-11 08:58:01'),(13,'25_26',841,NULL,NULL,6,'2026-06-11 09:32:40',NULL,'scorer','2026-06-11 09:32:40','scorer','2026-06-11 09:32:44');
/*!40000 ALTER TABLE `round` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `TW4_holding`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_holding` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_holding`;

--
-- Table structure for table `best_five`
--

DROP TABLE IF EXISTS `best_five`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `best_five` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT '0',
  `points_total` int NOT NULL DEFAULT '0',
  `points_best_1` int NOT NULL DEFAULT '0',
  `points_best_2` int NOT NULL DEFAULT '0',
  `points_best_3` int NOT NULL DEFAULT '0',
  `points_best_4` int NOT NULL DEFAULT '0',
  `points_best_5` int NOT NULL DEFAULT '0',
  `round_best_1` int NOT NULL DEFAULT '0',
  `round_best_2` int NOT NULL DEFAULT '0',
  `round_best_3` int NOT NULL DEFAULT '0',
  `round_best_4` int NOT NULL DEFAULT '0',
  `round_best_5` int NOT NULL DEFAULT '0',
  `points_movement` int NOT NULL DEFAULT '0',
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_best_five_season_player` (`season_year`,`row_id_player`),
  KEY `idx_best_five_player` (`row_id_player`),
  KEY `idx_best_five_season` (`season_year`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `best_five`
--

LOCK TABLES `best_five` WRITE;
/*!40000 ALTER TABLE `best_five` DISABLE KEYS */;
INSERT INTO `best_five` VALUES (1,'25_26',1,840,19,19,0,0,0,0,840,0,0,0,0,19,'scorer','2026-06-11 08:59:28'),(2,'25_26',2,840,18,18,0,0,0,0,840,0,0,0,0,18,'scorer','2026-06-11 08:59:28'),(3,'25_26',10,840,20,20,0,0,0,0,840,0,0,0,0,20,'scorer','2026-06-11 08:59:28'),(4,'25_26',11,840,17,17,0,0,0,0,840,0,0,0,0,17,'scorer','2026-06-11 08:59:28');
/*!40000 ALTER TABLE `best_five` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-17  9:04:38
