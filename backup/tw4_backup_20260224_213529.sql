-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: TW4
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
-- Table structure for table `card`
--

DROP TABLE IF EXISTS `card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `row_id_round` int NOT NULL,
  `row_id_player` int NOT NULL,
  `handicap` int NOT NULL,
  `score` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `unique_card` (`row_id_round`,`row_id_player`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card`
--

LOCK TABLES `card` WRITE;
/*!40000 ALTER TABLE `card` DISABLE KEYS */;
/*!40000 ALTER TABLE `card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_byhole`
--

DROP TABLE IF EXISTS `card_byhole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_byhole` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `row_id_card` int NOT NULL,
  `hole` int NOT NULL,
  `score` int NOT NULL,
  `shots` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `unique_card_by_hole` (`row_id_card`,`hole`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_byhole`
--

LOCK TABLES `card_byhole` WRITE;
/*!40000 ALTER TABLE `card_byhole` DISABLE KEYS */;
/*!40000 ALTER TABLE `card_byhole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_basic`
--

DROP TABLE IF EXISTS `config_basic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_basic` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `config_name` char(32) COLLATE utf8mb4_general_ci NOT NULL,
  `config_type` char(8) COLLATE utf8mb4_general_ci NOT NULL,
  `config_value_string` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `config_value_int` int NOT NULL,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `config_name_unique` (`config_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_basic`
--

LOCK TABLES `config_basic` WRITE;
/*!40000 ALTER TABLE `config_basic` DISABLE KEYS */;
INSERT INTO `config_basic` VALUES (1,'name_club','string','',0),(2,'initials_club','string','',0),(3,'number_club','int','',0),(4,'name_comp','string','',0),(5,'handicap_method','string','',0),(6,'fee_entry','int','',0),(7,'ident_season','string','',0);
/*!40000 ALTER TABLE `config_basic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_course`
--

DROP TABLE IF EXISTS `config_course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_course` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `name_club` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_hole` int NOT NULL,
  `gender` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `par` int NOT NULL,
  `stroke` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_course`
--

LOCK TABLES `config_course` WRITE;
/*!40000 ALTER TABLE `config_course` DISABLE KEYS */;
/*!40000 ALTER TABLE `config_course` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_played`
--

DROP TABLE IF EXISTS `course_played`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_played` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `name_course` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `name_club` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `ident_eclectic` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_played`
--

LOCK TABLES `course_played` WRITE;
/*!40000 ALTER TABLE `course_played` DISABLE KEYS */;
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
  `name_course` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `hole` int NOT NULL,
  `number_hole` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT=' ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_played_hole`
--

LOCK TABLES `course_played_hole` WRITE;
/*!40000 ALTER TABLE `course_played_hole` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_played_hole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `haggle_best_5`
--

DROP TABLE IF EXISTS `haggle_best_5`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `haggle_best_5` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `points_total` int NOT NULL,
  `best1` int NOT NULL,
  `best2` int NOT NULL,
  `best3` int NOT NULL,
  `best4` int NOT NULL,
  `best5` int NOT NULL,
  `movement` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `best_5_unique` (`ident_season`,`ident_player`,`number_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `haggle_best_5`
--

LOCK TABLES `haggle_best_5` WRITE;
/*!40000 ALTER TABLE `haggle_best_5` DISABLE KEYS */;
/*!40000 ALTER TABLE `haggle_best_5` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `haggle_eclectic`
--

DROP TABLE IF EXISTS `haggle_eclectic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `haggle_eclectic` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `ident_eclectic` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `score_total` int NOT NULL,
  `holeA` int NOT NULL,
  `holeB` int NOT NULL,
  `holeC` int NOT NULL,
  `holeD` int NOT NULL,
  `holeE` int NOT NULL,
  `holeF` int NOT NULL,
  `holeG` int NOT NULL,
  `holeH` int NOT NULL,
  `holeI` int NOT NULL,
  `movement` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `eclectic_entry` (`ident_season`,`ident_eclectic`,`ident_player`,`number_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `haggle_eclectic`
--

LOCK TABLES `haggle_eclectic` WRITE;
/*!40000 ALTER TABLE `haggle_eclectic` DISABLE KEYS */;
/*!40000 ALTER TABLE `haggle_eclectic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `haggle_team`
--

DROP TABLE IF EXISTS `haggle_team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `haggle_team` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `number_team` int NOT NULL,
  `name_team` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `points_total` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `haggle_team`
--

LOCK TABLES `haggle_team` WRITE;
/*!40000 ALTER TABLE `haggle_team` DISABLE KEYS */;
/*!40000 ALTER TABLE `haggle_team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `haggle_team_control`
--

DROP TABLE IF EXISTS `haggle_team_control`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `haggle_team_control` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `haggle_state` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `team_size` int NOT NULL,
  `number_round` int NOT NULL,
  `count_teams` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `season_id_unique` (`ident_season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `haggle_team_control`
--

LOCK TABLES `haggle_team_control` WRITE;
/*!40000 ALTER TABLE `haggle_team_control` DISABLE KEYS */;
/*!40000 ALTER TABLE `haggle_team_control` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `haggle_team_member`
--

DROP TABLE IF EXISTS `haggle_team_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `haggle_team_member` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `number_team` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `points_total` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `haggle_team_member`
--

LOCK TABLES `haggle_team_member` WRITE;
/*!40000 ALTER TABLE `haggle_team_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `haggle_team_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `handicap_change`
--

DROP TABLE IF EXISTS `handicap_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `handicap_change` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `points_scored` int NOT NULL,
  `points_adjusted` int NOT NULL,
  `handicap_from` int NOT NULL,
  `handicap_to` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `handicap_change`
--

LOCK TABLES `handicap_change` WRITE;
/*!40000 ALTER TABLE `handicap_change` DISABLE KEYS */;
/*!40000 ALTER TABLE `handicap_change` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hist_card`
--

DROP TABLE IF EXISTS `hist_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hist_card` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `handicap` int NOT NULL,
  `score` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hist_card`
--

LOCK TABLES `hist_card` WRITE;
/*!40000 ALTER TABLE `hist_card` DISABLE KEYS */;
/*!40000 ALTER TABLE `hist_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hist_card_byhole`
--

DROP TABLE IF EXISTS `hist_card_byhole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hist_card_byhole` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `hole` int NOT NULL,
  `score` int NOT NULL,
  `shots` int NOT NULL,
  `points` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hist_card_byhole`
--

LOCK TABLES `hist_card_byhole` WRITE;
/*!40000 ALTER TABLE `hist_card_byhole` DISABLE KEYS */;
/*!40000 ALTER TABLE `hist_card_byhole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hist_handicap`
--

DROP TABLE IF EXISTS `hist_handicap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hist_handicap` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `points_scored` int NOT NULL,
  `points_adjusted` int NOT NULL,
  `handicap_from` int NOT NULL,
  `handicap_to` int NOT NULL,
  `updated_because` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hist_handicap`
--

LOCK TABLES `hist_handicap` WRITE;
/*!40000 ALTER TABLE `hist_handicap` DISABLE KEYS */;
/*!40000 ALTER TABLE `hist_handicap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hist_result`
--

DROP TABLE IF EXISTS `hist_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hist_result` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_round` int NOT NULL,
  `type_result` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_result` int NOT NULL,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `value_result` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hist_result`
--

LOCK TABLES `hist_result` WRITE;
/*!40000 ALTER TABLE `hist_result` DISABLE KEYS */;
/*!40000 ALTER TABLE `hist_result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hist_round`
--

DROP TABLE IF EXISTS `hist_round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hist_round` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `number_round` int NOT NULL,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `name_comp` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `name_course` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `date_played` date NOT NULL,
  `scorer` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `count_entries` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_history` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hist_round`
--

LOCK TABLES `hist_round` WRITE;
/*!40000 ALTER TABLE `hist_round` DISABLE KEYS */;
/*!40000 ALTER TABLE `hist_round` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `name_last` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `name_first` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `gender` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `handicap` int NOT NULL DEFAULT '0',
  `index_metaphone` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ident_public` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'A',
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `ident_player_unique` (`ident_player`),
  UNIQUE KEY `ident_public_unique` (`ident_public`),
  KEY `index_metaphone_access` (`index_metaphone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player`
--

LOCK TABLES `player` WRITE;
/*!40000 ALTER TABLE `player` DISABLE KEYS */;
/*!40000 ALTER TABLE `player` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_save`
--

DROP TABLE IF EXISTS `player_save`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_save` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_player` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `name_last` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `name_first` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `gender` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `handicap` int NOT NULL DEFAULT '0',
  `index_metaphone` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ident_public` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'A',
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `ident_player_unique` (`ident_player`),
  UNIQUE KEY `ident_public_unique` (`ident_public`),
  KEY `index_metaphone_access` (`index_metaphone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_save`
--

LOCK TABLES `player_save` WRITE;
/*!40000 ALTER TABLE `player_save` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_save` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `result` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `type_result` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_result` int NOT NULL,
  `row_id_player` int NOT NULL,
  `value_result` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result`
--

LOCK TABLES `result` WRITE;
/*!40000 ALTER TABLE `result` DISABLE KEYS */;
/*!40000 ALTER TABLE `result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `round` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `number_round` int NOT NULL,
  `ident_season` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `name_comp` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `name_course` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `date_played` date NOT NULL,
  `scorer` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `count_entries` int NOT NULL,
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `round`
--

LOCK TABLES `round` WRITE;
/*!40000 ALTER TABLE `round` DISABLE KEYS */;
/*!40000 ALTER TABLE `round` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `name_user` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `user_role` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'admin','$2y$10$kcoWrUySBac9yuT0jM93B.edAeyIm1LymGTsE.4F7E9R/iYi6k/5i','admin','2025-05-02 09:35:52','2025-09-20 17:44:08');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visit_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `referrer` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-24  8:36:00
