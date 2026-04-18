-- Simplify course_played_hole by removing redundant columns
-- Keep course_played_id + number_hole for consistency with course_club

ALTER TABLE course_played_hole
    DROP INDEX unique_course_played_hole;

ALTER TABLE course_played_hole
    DROP COLUMN hole,
    DROP COLUMN name_course;
