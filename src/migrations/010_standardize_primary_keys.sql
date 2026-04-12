-- Standardize all primary keys to use row_id naming convention
-- Author: Ned Bollard
-- Description: Rename all *_id primary keys to row_id for consistency

-- Rename players.player_id to row_id
ALTER TABLE players CHANGE COLUMN player_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename courses.course_id to row_id
ALTER TABLE courses CHANGE COLUMN course_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename rounds.round_id to row_id
ALTER TABLE rounds CHANGE COLUMN round_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename hole_scores.score_id to row_id
ALTER TABLE hole_scores CHANGE COLUMN score_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename holes.hole_id to row_id
ALTER TABLE holes CHANGE COLUMN hole_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename score_cards.card_id to row_id
ALTER TABLE score_cards CHANGE COLUMN card_id row_id INT NOT NULL AUTO_INCREMENT;

-- Update foreign key references
-- Update rounds.course_id to reference courses.row_id (keep course_id as FK)
-- Update score_cards.round_id to reference rounds.row_id (keep round_id as FK)
-- Update score_cards.player_id to reference players.row_id (keep player_id as FK)
-- Update hole_scores.card_id to reference score_cards.row_id (keep card_id as FK)
-- Update hole_scores.hole_id to reference holes.row_id (keep hole_id as FK)
-- Update holes.course_id to reference courses.row_id (keep course_id as FK)

-- Add comments to clarify the naming convention
ALTER TABLE players MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE courses MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE rounds MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE staff MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE holes MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE hole_scores MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE score_cards MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
