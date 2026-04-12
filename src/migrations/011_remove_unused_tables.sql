-- Remove unused and inadequate tables
-- Author: Ned Bollard
-- Description: Drop tables that are not referenced in current code and based on invalid assumptions

-- Drop tables in order to avoid foreign key constraints
DROP TABLE IF EXISTS hole_scores;
DROP TABLE IF EXISTS score_cards;
DROP TABLE IF EXISTS rounds;
DROP TABLE IF EXISTS holes;
DROP TABLE IF EXISTS courses;

-- Note: hole_scores is being dropped as it depends on the tables being removed
-- These tables were based on assumptions that don't hold in the current application design
-- The current TW4 application focuses on staff management and player management only
-- Golf round tracking, course management, and score tracking will be redesigned later if needed
