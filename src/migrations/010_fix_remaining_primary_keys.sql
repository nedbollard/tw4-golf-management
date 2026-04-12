-- Fix remaining primary keys to use row_id naming convention
-- Author: Ned Bollard
-- Description: Rename remaining *_id primary keys to row_id for consistency

-- Rename holes.hole_id to row_id
ALTER TABLE holes CHANGE COLUMN hole_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename hole_scores.score_id to row_id
ALTER TABLE hole_scores CHANGE COLUMN score_id row_id INT NOT NULL AUTO_INCREMENT;

-- Rename score_cards.card_id to row_id
ALTER TABLE score_cards CHANGE COLUMN card_id row_id INT NOT NULL AUTO_INCREMENT;

-- Add comments to clarify the naming convention
ALTER TABLE holes MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE hole_scores MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
ALTER TABLE score_cards MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - standardized row_id';
