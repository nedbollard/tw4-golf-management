-- Migration 022: Create live card and card_by_hole tables in TW4_live
-- Run against TW4_live database only.

USE TW4_live;

CREATE TABLE IF NOT EXISTS card (
    row_id INT NOT NULL AUTO_INCREMENT,
    row_id_round INT NOT NULL,
    row_id_player INT NOT NULL,
    handicap INT NOT NULL,
    score INT NOT NULL,
    points INT NOT NULL,
    updated_by VARCHAR(32) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_card_round_player (row_id_round, row_id_player),
    INDEX idx_card_round (row_id_round),
    INDEX idx_card_player (row_id_player),
    CONSTRAINT fk_card_round FOREIGN KEY (row_id_round)
        REFERENCES round (row_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS card_by_hole (
    row_id INT NOT NULL AUTO_INCREMENT,
    row_id_card INT NOT NULL,
    hole INT NOT NULL,
    score INT NOT NULL,
    shots INT NOT NULL,
    points INT NOT NULL,
    updated_by VARCHAR(32) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_card_hole (row_id_card, hole),
    INDEX idx_card_by_hole_card (row_id_card),
    CONSTRAINT fk_card_by_hole_card FOREIGN KEY (row_id_card)
        REFERENCES card (row_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
