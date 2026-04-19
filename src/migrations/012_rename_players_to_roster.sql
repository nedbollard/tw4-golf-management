-- Rename players table to roster for better naming convention
-- Author: Ned Bollard
-- Description: Rename 'players' table to 'roster' following singular naming convention

-- Rename the table from players to roster only when required.
SET @players_exists := (
		SELECT COUNT(*)
		FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = 'players'
);
SET @roster_exists := (
		SELECT COUNT(*)
		FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = 'roster'
);
SET @rename_sql := IF(
		@players_exists > 0 AND @roster_exists = 0,
		'RENAME TABLE players TO roster',
	'DO 0'
);
PREPARE rename_stmt FROM @rename_sql;
EXECUTE rename_stmt;
DEALLOCATE PREPARE rename_stmt;

-- Update comments to reflect the new name and purpose
ALTER TABLE roster MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id for roster entry';
ALTER TABLE roster MODIFY COLUMN player_identifier VARCHAR(50) NOT NULL COMMENT 'Unique roster identifier (e.g., JohnD) for golf player';
ALTER TABLE roster MODIFY COLUMN first_name VARCHAR(100) NOT NULL COMMENT 'Player first name';
ALTER TABLE roster MODIFY COLUMN last_name VARCHAR(100) NOT NULL COMMENT 'Player last name';
ALTER TABLE roster MODIFY COLUMN alias VARCHAR(50) NULL COMMENT 'Player alias/nickname for display';
ALTER TABLE roster MODIFY COLUMN gender ENUM('male', 'female') NOT NULL COMMENT 'Player gender';
ALTER TABLE roster MODIFY COLUMN handicap INT DEFAULT 0 COMMENT 'Golf handicap index';
ALTER TABLE roster MODIFY COLUMN status ENUM('active', 'scored', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Roster status';
ALTER TABLE roster MODIFY COLUMN first_play_date DATE NULL COMMENT 'Date of first played round';

-- Add table comment
ALTER TABLE roster COMMENT = 'Golf player roster - contains all player information and status';
