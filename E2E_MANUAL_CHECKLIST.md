# TW4 Manual E2E Checklist (Fresh DB)

Date: __________
Tester: __________
Build/Commit: __________

## Credentials
- Admin username: admin
- Admin password: hash_house

## 1. Fresh Database Install
- [ ] Start containers
  - `docker compose up -d`
- [ ] Load DB password from .env
  - `DB_PASSWORD=$(awk -F= '/^DB_PASSWORD=/{print substr($0,index($0,"=")+1); exit}' .env)`
- [ ] Recreate databases
  - `docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "DROP DATABASE IF EXISTS TW4_base; DROP DATABASE IF EXISTS TW4_live; CREATE DATABASE TW4_base; CREATE DATABASE TW4_live;"`
- [ ] Apply TW4_base migrations (exclude live-only + snapshot)
  - `for m in $(ls src/migrations/*.sql | grep -v '017_create_live_database_schema.sql' | grep -v '018_seed_live_round.sql' | grep -v '019_round_workflow_and_lock.sql' | grep -v '021_live_round_start_defaults.sql' | grep -v '022_live_card_tables.sql' | grep -v '024_live_results_table.sql' | grep -v '025_round_season_year.sql' | grep -v '026_card_handicap_audit.sql' | grep -v '999_current_schema.sql' | sort); do docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root TW4_base < "$m"; done`
- [ ] Create TW4_live.round table (required for current app workflow)
  - `docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE TW4_live; CREATE TABLE IF NOT EXISTS round ( row_id int NOT NULL AUTO_INCREMENT, season_year char(5) DEFAULT NULL, number_round int NOT NULL, round_date date DEFAULT NULL, course_played_id int DEFAULT NULL, workflow_step enum('not_started','card_entry_open','results_presented','finished','cancelled') NOT NULL DEFAULT 'not_started', card_count int NOT NULL DEFAULT '0', results_presented_at timestamp NULL DEFAULT NULL, finished_at timestamp NULL DEFAULT NULL, locked_by_staff_id int DEFAULT NULL, lock_acquired_at timestamp NULL DEFAULT NULL, lock_expires_at timestamp NULL DEFAULT NULL, lock_released_by_staff_id int DEFAULT NULL, lock_released_at timestamp NULL DEFAULT NULL, lock_release_reason enum('logout','session_expired','admin_forced','finished') DEFAULT NULL, updated_by varchar(100) DEFAULT NULL, updated_ts timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (row_id), UNIQUE KEY uk_round_season_number (season_year,number_round), KEY idx_round_workflow_step (workflow_step), KEY idx_round_locked_by_staff_id (locked_by_staff_id), KEY idx_round_lock_expires_at (lock_expires_at), KEY idx_round_course_played_id (course_played_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;"`
- [ ] Apply live migrations
  - `for m in src/migrations/018_seed_live_round.sql src/migrations/019_round_workflow_and_lock.sql src/migrations/021_live_round_start_defaults.sql src/migrations/022_live_card_tables.sql src/migrations/024_live_results_table.sql src/migrations/025_round_season_year.sql src/migrations/026_card_handicap_audit.sql; do docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root TW4_live < "$m"; done`
- [ ] Verify seeded admin exists
  - `docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -N -s -u root -e "SELECT username, role, is_active FROM TW4_base.staff WHERE username='admin';"`
  - Expected: `admin\tadmin\t1`

## 2. Admin Setup Flow
App URL: http://localhost:8084

- [ ] Login as admin at `/login`
- [ ] Basic configuration complete at `/config`
  - Minimum: `club_name`, `competition_name`, `season_year`
- [ ] Configure course holes at `/course-club`
- [ ] Configure at least one playable course at `/course-played`
- [ ] Ensure staff has at least:
  - one admin
  - one scorer
  at `/staff`

## 3. Scorer Flow (First Pass, Empty Live Round)
- [ ] Logout admin, login as scorer
- [ ] Create/amend roster at `/roster`
- [ ] Start round from scorer menu (Step 1) via `/rounds/start`
- [ ] Enter cards (Step 2) via `/scores/enter`
- [ ] Present results (Step 3) via `/scores/present-results`
- [ ] Confirm results recorded screen appears

Checks:
- [ ] Workflow moved to `results_presented`
- [ ] Present results required at least 4 cards

## 4. Admin Reset State
- [ ] Logout scorer, login as admin
- [ ] Open `/admin/scoring-state`
- [ ] Click `Reset to Cards Entry Open`
- [ ] Confirm success message

Checks:
- [ ] Reset button was enabled only in `results_presented`
- [ ] Workflow moved back to `card_entry_open`
- [ ] Live results were cleared

## 5. Scorer Flow (Second Pass, Existing Round Record)
- [ ] Logout admin, login as scorer
- [ ] Create/amend roster at `/roster`
- [ ] Confirm existing round context remains (do not start a new round)
- [ ] Enter/update cards via `/scores/enter`
- [ ] Present results again via `/scores/present-results`
- [ ] Confirm results recorded screen appears again

## 6. Pass/Fail Summary
- [ ] PASS all critical checkpoints
- [ ] FAIL if any enforced state transition is bypassed

Notes:
- __________________________________________________________
- __________________________________________________________
- __________________________________________________________
