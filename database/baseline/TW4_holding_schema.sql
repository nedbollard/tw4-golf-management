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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `TW4_holding` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `TW4_holding`;

DROP TABLE IF EXISTS `best_five_scores`;
CREATE TABLE `best_five_scores` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT 0,
  `points_total` int NOT NULL DEFAULT 0,
  `points_best_1` int NOT NULL DEFAULT 0,
  `points_best_2` int NOT NULL DEFAULT 0,
  `points_best_3` int NOT NULL DEFAULT 0,
  `points_best_4` int NOT NULL DEFAULT 0,
  `points_best_5` int NOT NULL DEFAULT 0,
  `round_best_1` int NOT NULL DEFAULT 0,
  `round_best_2` int NOT NULL DEFAULT 0,
  `round_best_3` int NOT NULL DEFAULT 0,
  `round_best_4` int NOT NULL DEFAULT 0,
  `round_best_5` int NOT NULL DEFAULT 0,
  `points_movement` int NOT NULL DEFAULT 0,
  `updated_by` varchar(100) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_best_five_scores_season_player` (`season_year`,`row_id_player`),
  KEY `idx_best_five_scores_player` (`row_id_player`),
  KEY `idx_best_five_scores_season` (`season_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `eclectic_scores`;
CREATE TABLE `eclectic_scores` (
  `row_id` int NOT NULL AUTO_INCREMENT,
  `season_year` char(5) NOT NULL,
  `ident_eclectic` varchar(16) NOT NULL,
  `row_id_player` int NOT NULL,
  `number_round_movement` int NOT NULL DEFAULT 0,
  `score_total` int NOT NULL DEFAULT 0,
  `score_hole_1` int NOT NULL DEFAULT 0,
  `score_hole_2` int NOT NULL DEFAULT 0,
  `score_hole_3` int NOT NULL DEFAULT 0,
  `score_hole_4` int NOT NULL DEFAULT 0,
  `score_hole_5` int NOT NULL DEFAULT 0,
  `score_hole_6` int NOT NULL DEFAULT 0,
  `score_hole_7` int NOT NULL DEFAULT 0,
  `score_hole_8` int NOT NULL DEFAULT 0,
  `score_hole_9` int NOT NULL DEFAULT 0,
  `updated_by` varchar(100) NOT NULL,
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `uk_eclectic_scores_season_ident_player` (`season_year`,`ident_eclectic`,`row_id_player`),
  KEY `idx_eclectic_scores_player` (`row_id_player`),
  KEY `idx_eclectic_scores_season` (`season_year`),
  KEY `idx_eclectic_scores_ident` (`ident_eclectic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
