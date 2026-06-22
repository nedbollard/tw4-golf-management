
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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_live` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_live`;
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
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_card_player` (`row_id_player`),
  KEY `idx_card_player` (`row_id_player`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `updated_by` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_card_hole` (`row_id_card`,`hole`),
  KEY `idx_card_by_hole_card` (`row_id_card`),
  CONSTRAINT `fk_card_by_hole_card` FOREIGN KEY (`row_id_card`) REFERENCES `card` (`row_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `type_result` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `number_result` int NOT NULL,
  `player_identifier` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `value_result` int NOT NULL,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `idx_results_type_result` (`type_result`),
  KEY `idx_results_player_identifier` (`player_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `best_five_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `best_five_scores` (
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
  UNIQUE KEY `uk_best_five_scores_season_player` (`season_year`,`row_id_player`),
  KEY `idx_best_five_scores_player` (`row_id_player`),
  KEY `idx_best_five_scores_season` (`season_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eclectic_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eclectic_scores` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `ident_eclectic` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `season_year` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT '0',
  `score_total` int NOT NULL DEFAULT '0',
  `score_hole_1` int NOT NULL DEFAULT '0',
  `score_hole_2` int NOT NULL DEFAULT '0',
  `score_hole_3` int NOT NULL DEFAULT '0',
  `score_hole_4` int NOT NULL DEFAULT '0',
  `score_hole_5` int NOT NULL DEFAULT '0',
  `score_hole_6` int NOT NULL DEFAULT '0',
  `score_hole_7` int NOT NULL DEFAULT '0',
  `score_hole_8` int NOT NULL DEFAULT '0',
  `score_hole_9` int NOT NULL DEFAULT '0',
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_eclectic_scores_season_ident_player` (`season_year`,`ident_eclectic`,`row_id_player`),
  KEY `idx_eclectic_scores_player` (`row_id_player`),
  KEY `idx_eclectic_scores_season` (`season_year`),
  KEY `idx_eclectic_scores_ident` (`ident_eclectic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

