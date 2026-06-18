-- Cleanup: remove leftover temporary column from course_played_hole.
-- The column may exist if an earlier migration attempt failed mid-run.

DROP PROCEDURE IF EXISTS cleanup_course_played_hole_temp_column;
DELIMITER $$
CREATE PROCEDURE cleanup_course_played_hole_temp_column()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND column_name = 'number_hole_played_temp'
    ) THEN
        ALTER TABLE course_played_hole
            DROP COLUMN number_hole_played_temp;
    END IF;
END$$
DELIMITER ;

CALL cleanup_course_played_hole_temp_column();
DROP PROCEDURE IF EXISTS cleanup_course_played_hole_temp_column;
