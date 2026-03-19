-- General Purpose Application Log Table
CREATE TABLE IF NOT EXISTS application_log (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_timestamp (timestamp),
    INDEX idx_level (level),
    INDEX idx_event_type (event_type),
    INDEX idx_username (username)
);
