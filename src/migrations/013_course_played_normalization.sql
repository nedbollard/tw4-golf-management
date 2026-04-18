-- Normalize Course Played tables while preserving existing data

-- Ensure `ident_eclectic` uses bounded varchar for consistency
ALTER TABLE course_played
    MODIFY COLUMN ident_eclectic VARCHAR(16) NOT NULL;

-- Add relational key from course_played_hole to course_played
ALTER TABLE course_played_hole
    ADD COLUMN course_played_id INT NULL AFTER row_id;

UPDATE course_played_hole cph
JOIN course_played cp ON cp.name_course = cph.name_course
SET cph.course_played_id = cp.row_id
WHERE cph.course_played_id IS NULL;

-- Require relation and add integrity/index rules
ALTER TABLE course_played_hole
    MODIFY COLUMN course_played_id INT NOT NULL,
    ADD UNIQUE KEY unique_course_played_hole (course_played_id, hole),
    ADD UNIQUE KEY unique_course_played_number_hole (course_played_id, number_hole),
    ADD CONSTRAINT fk_course_played_hole_course_played
        FOREIGN KEY (course_played_id) REFERENCES course_played(row_id)
        ON DELETE CASCADE;
