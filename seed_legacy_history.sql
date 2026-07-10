-- Legacy history seed generated 2026-07-11T11:09:36+12:00
START TRANSACTION;
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 5, '2022-11-09', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 5, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 6, '2022-11-16', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 6, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 7, '2022-11-23', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 7, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 8, '2022-11-30', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 8, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 9, '2022-12-07', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 9, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 10, '2022-12-14', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 10, 0, 'none', '["41_Eclectic_Legacy.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 11, '2022-12-21', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 11, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 12, '2022-12-28', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 12, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 13, '2023-01-04', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 13, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 14, '2023-01-18', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 14, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 15, '2023-01-25', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 15, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 16, '2023-02-01', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 16, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 17, '2023-02-08', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 17, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 18, '2023-02-15', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 18, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 19, '2023-02-22', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 19, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 20, '2023-03-01', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 20, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 21, '2023-03-08', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 21, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 22, '2023-03-15', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 22, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 23, '2023-03-22', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 23, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 24, '2023-03-29', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('22_23', 24, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 1, '2023-10-04', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 1, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 2, '2023-10-11', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 2, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 3, '2023-10-18', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 3, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 4, '2023-10-25', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 4, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 5, '2023-11-01', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 5, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 6, '2023-11-08', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 6, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 7, '2023-11-18', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 7, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 8, '2023-11-22', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 8, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 9, '2023-11-29', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 9, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 10, '2023-12-06', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 10, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 11, '2023-12-13', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 11, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 12, '2023-12-20', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 12, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 13, '2024-01-10', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 13, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 14, '2024-01-17', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 14, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 15, '2024-01-24', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 15, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 16, '2024-01-31', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 16, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 17, '2024-02-07', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 17, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 18, '2024-02-14', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 18, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 19, '2024-02-21', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 19, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 20, '2024-02-28', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 20, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 21, '2024-03-06', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 21, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 22, '2024-03-13', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('23_24', 22, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 1, '2024-10-02', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 1, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 2, '2024-10-09', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 2, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 3, '2024-10-16', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 3, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 4, '2024-10-23', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 4, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 5, '2024-11-06', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 5, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 6, '2024-11-13', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 6, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 7, '2024-11-20', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 7, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 8, '2024-11-27', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 8, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 9, '2024-12-04', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 9, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 10, '2024-12-11', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 10, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 11, '2024-12-18', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 11, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 12, '2025-01-08', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 12, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 13, '2025-01-15', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 13, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 14, '2025-01-22', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 14, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 15, '2025-01-29', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 15, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 16, '2025-02-05', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 16, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 17, '2025-02-12', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 17, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 18, '2025-02-19', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 18, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 19, '2025-02-26', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 19, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 20, '2025-03-05', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 20, 0, 'none', '["41_Eclectic_Whites.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 21, '2025-03-26', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 21, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 22, '2025-04-02', 0, 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('24_25', 22, 0, 'none', '["41_Eclectic_Blues.html"]', 'legacy_import', NOW(), 'legacy_import', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
COMMIT;
