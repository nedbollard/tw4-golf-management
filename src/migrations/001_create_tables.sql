-- TW4 Golf Management System Database Schema
-- Clean, normalized structure with proper relationships

-- Staff table (admins and scorers)
CREATE TABLE staff (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'scorer') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Players table (no login required)
CREATE TABLE players (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    member_identifier VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    alias VARCHAR(50) NULL UNIQUE,
    gender ENUM('male', 'female') NOT NULL,
    handicap INT DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_identifier (member_identifier),
    INDEX idx_alias (alias),
    INDEX idx_status (status)
);

-- Courses table
CREATE TABLE courses (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    par_total INT NOT NULL DEFAULT 72,
    tee_color VARCHAR(20) NOT NULL DEFAULT 'white',
    slope_rating DECIMAL(4,1) NOT NULL DEFAULT 113.0,
    course_rating DECIMAL(4,1) NOT NULL DEFAULT 70.0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (is_active)
);

-- Holes table (course-specific hole information)
CREATE TABLE holes (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    hole_number INT NOT NULL,
    par INT NOT NULL,
    stroke_index INT NOT NULL,
    hole_description VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(row_id) ON DELETE CASCADE,
    UNIQUE KEY uk_course_hole (course_id, hole_number),
    INDEX idx_course (course_id),
    INDEX idx_hole_number (hole_number)
);

-- Rounds table (active rounds in progress)
CREATE TABLE rounds (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    staff_id INT NOT NULL,
    round_number INT NOT NULL,
    round_date DATE NOT NULL,
    tee_time TIME NULL,
    weather_conditions VARCHAR(100) NULL,
    status ENUM('setup', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'setup',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(row_id),
    FOREIGN KEY (staff_id) REFERENCES staff(row_id),
    UNIQUE KEY uk_course_round (course_id, round_number),
    INDEX idx_course (course_id),
    INDEX idx_staff (staff_id),
    INDEX idx_date (round_date),
    INDEX idx_status (status)
);

-- Score cards table (completed rounds)
CREATE TABLE score_cards (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    player_id INT NOT NULL,
    total_score INT NOT NULL DEFAULT 0,
    total_points INT NOT NULL DEFAULT 0,
    handicap_used INT NULL,
    status ENUM('pending', 'verified', 'final') NOT NULL DEFAULT 'pending',
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(row_id),
    FOREIGN KEY (player_id) REFERENCES players(row_id),
    FOREIGN KEY (verified_by) REFERENCES staff(row_id),
    UNIQUE KEY uk_round_player (round_id, player_id),
    INDEX idx_round (round_id),
    INDEX idx_player (player_id),
    INDEX idx_status (status)
);

-- Hole scores table (individual hole scores)
CREATE TABLE hole_scores (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    hole_id INT NOT NULL,
    score INT NOT NULL,
    shots INT NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES score_cards(row_id) ON DELETE CASCADE,
    FOREIGN KEY (hole_id) REFERENCES holes(row_id),
    UNIQUE KEY uk_card_hole (card_id, hole_id),
    INDEX idx_card (card_id),
    INDEX idx_hole (hole_id)
);


-- Audit log table (for tracking changes)
CREATE TABLE audit_log (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NULL,
    action ENUM('create', 'update', 'delete', 'login', 'logout') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    staff_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(row_id),
    INDEX idx_table (table_name),
    INDEX idx_action (action),
    INDEX idx_staff (staff_id),
    INDEX idx_created (created_at)
);
