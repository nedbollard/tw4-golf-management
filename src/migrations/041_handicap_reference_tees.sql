-- Effective-dated club tee ratings used by the scorer handicap reference tool.

CREATE TABLE IF NOT EXISTS handicap_reference_tees (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    club_number INT NOT NULL,
    gender CHAR(1) NOT NULL,
    tee_name VARCHAR(24) NOT NULL,
    course_rating DECIMAL(4,1) NOT NULL,
    par SMALLINT NOT NULL,
    slope SMALLINT NOT NULL,
    handicap_allowance DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    updated_by VARCHAR(32) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_handicap_reference (club_number, gender, tee_name, effective_from),
    KEY idx_handicap_reference_current (club_number, gender, effective_from, effective_to),
    CONSTRAINT chk_handicap_reference_gender CHECK (gender IN ('M', 'F')),
    CONSTRAINT chk_handicap_reference_rating CHECK (course_rating > 0),
    CONSTRAINT chk_handicap_reference_par CHECK (par > 0),
    CONSTRAINT chk_handicap_reference_slope CHECK (slope BETWEEN 55 AND 155),
    CONSTRAINT chk_handicap_reference_allowance CHECK (handicap_allowance > 0 AND handicap_allowance <= 100),
    CONSTRAINT chk_handicap_reference_dates CHECK (effective_to IS NULL OR effective_to >= effective_from)
);

INSERT INTO handicap_reference_tees (
    club_number,
    gender,
    tee_name,
    course_rating,
    par,
    slope,
    handicap_allowance,
    effective_from,
    effective_to,
    updated_by
) VALUES
    (294, 'M', 'White', 62.9, 66, 107, 100.00, '2025-12-02', NULL, 'system'),
    (294, 'F', 'Yellow', 65.2, 66, 109, 100.00, '2025-12-02', NULL, 'system')
ON DUPLICATE KEY UPDATE
    course_rating = VALUES(course_rating),
    par = VALUES(par),
    slope = VALUES(slope),
    handicap_allowance = VALUES(handicap_allowance),
    effective_to = VALUES(effective_to);