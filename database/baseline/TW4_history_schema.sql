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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_history` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_history`;

DROP TABLE IF EXISTS `card_by_hole`;
DROP TABLE IF EXISTS `card`;
DROP TABLE IF EXISTS `results`;
DROP TABLE IF EXISTS `round`;

CREATE TABLE `round` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `number_round` int NOT NULL,
  `round_date` date DEFAULT NULL,
  `course_played_id` int DEFAULT NULL,
  `card_count` int NOT NULL DEFAULT 0,
  `results_presented_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_ts` timestamp NULL DEFAULT NULL,
  `hist_updated_by` varchar(100) NOT NULL,
  `hist_updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_history_round_business` (`season_year`,`number_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
