-- Fix hole numbering: convert 0-based (0-8) to 1-based (1-9)
-- This migration corrects the mapping table for courses with incorrectly numbered holes.

DROP PROCEDURE IF EXISTS fix_hole_numbering;
DELIMITER $$
CREATE PROCEDURE fix_hole_numbering()
BEGIN
    DECLARE v_index_exists INT;

    -- Check if the unique index exists and drop it temporarily
    SELECT COUNT(*) INTO v_index_exists FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'course_played_hole'
      AND index_name = 'unique_course_played_number_hole_played'
      AND seq_in_index = 1;

    IF v_index_exists > 0 THEN
        ALTER TABLE course_played_hole DROP INDEX unique_course_played_number_hole_played;
    END IF;

    -- Update holes that are numbered 0-8 to 1-9
    UPDATE course_played_hole
    SET number_hole_played = number_hole_played + 1
    WHERE number_hole_played BETWEEN 0 AND 8;

    -- Recreate the unique index
    IF v_index_exists > 0 THEN
        CREATE UNIQUE INDEX unique_course_played_number_hole_played
            ON course_played_hole (course_played_id, number_hole_played);
    END IF;
END$$
DELIMITER ;

CALL fix_hole_numbering();
DROP PROCEDURE IF EXISTS fix_hole_numbering;
