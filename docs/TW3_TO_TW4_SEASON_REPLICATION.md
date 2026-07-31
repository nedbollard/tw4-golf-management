# TW3-to-TW4 Season Replication: Complete Process Guide

**Date Created**: 2026-07-28  
**Purpose**: Replicate TW3 season data (25_26) into TW4 with automated workflow testing  
**Status**: Development/Testing (remove automation code before production)

---

## Table of Contents

1. [Problem Statement](#problem-statement)
2. [Solution Architecture](#solution-architecture)
3. [Code Changes Required](#code-changes-required)
4. [Setup & Execution](#setup--execution)
5. [Process Workflow](#process-workflow)
6. [Troubleshooting](#troubleshooting)
7. [Production Considerations](#production-considerations)
8. [Key Files](#key-files)

---

## Problem Statement

**Goal**: Replicate season 25_26 from TW3 into TW4, fully testing the scoring workflow without manual UI interaction.

**Challenges**:
- Need to validate entire round finishing workflow (handicaps, best-five, eclectic, export, history)
- Manual process is tedious for multiple rounds
- Must use **real production code**, not mocked versions
- Must pause between rounds to allow user configuration (team setup, handicap adjustments)

**Solution**: Build a 6-stage automation orchestrator that calls the actual PHP workflow service via API endpoint.

---

## Solution Architecture

### High-Level Flow

```
tw3-season-automation.sh (bash orchestrator)
│
├─ Stage 1: Start Round
│  └─ Query TW3 hist_round for date_played, name_course
│     Update TW4_live.round with metadata
│
├─ Stage 2: Update Players
│  ├─ Create new players from TW3
│  └─ Adjust handicaps to start-of-round state
│
├─ Stage 3: Load Cards
│  └─ Call existing tw3-round-replay.sh load
│
├─ Stage 4: Finish Round via API
│  └─ POST /api/automation/finish-round (requires AUTOMATION_TOKEN)
│     └─ Calls RoundController::automationFinishRound()
│        └─ Calls RoundWorkflowService::finishRound() (real production code)
│           ├─ Apply handicap updates
│           ├─ Refresh best-five scores
│           ├─ Refresh eclectic calculations
│           ├─ Create history snapshot
│           ├─ Export HTML snapshots
│           └─ Update workflow state
│
├─ Stage 5: Set Closest to the Pin
│  └─ Query TW3 hist_result where type_result='C_P'
│     Insert into TW4 results table
│
└─ Stage 6: Compare Results
   └─ Call existing tw3-round-replay.sh compare
      └─ Validate handicap, best-five, eclectic match TW3
```

### Why This Approach?

✅ **Real Code**: Stage 4 calls the actual `RoundWorkflowService::finishRound()`, not mocked  
✅ **Full Test**: Exercises entire workflow: handicaps, best-five, eclectic, export, history  
✅ **Automation**: 6 stages fully orchestrated, one command per round  
✅ **Pausable**: Natural break between rounds for user config/team updates  
✅ **Validated**: Compare stage confirms TW4 matches TW3 exactly  

---

## Code Changes Required

### 1. Add PHP Endpoint to RoundController

**File**: `src/Controllers/RoundController.php`

**Change**: Add `automationFinishRound()` method before final closing brace

```php
/**
 * AUTOMATION ONLY: API endpoint for season replication
 * POST /api/automation/finish-round
 * Requires: X-Automation-Token header
 * Returns: JSON {success: bool, message: string}
 *
 * TO PRODUCTION REVIEWER: DELETE THIS METHOD before going live
 */
public function automationFinishRound(): void
{
    header('Content-Type: application/json');

    // Verify automation token
    $token = $_SERVER['HTTP_X_AUTOMATION_TOKEN'] ?? '';
    $expectedToken = getenv('AUTOMATION_TOKEN');
    if (!$expectedToken || $token !== $expectedToken) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    try {
        $round = $this->roundWorkflowService->getPermanentRound();
        if (!$round) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No permanent round found']);
            return;
        }

        $roundId = (int) ($round['round_id'] ?? $round['row_id'] ?? 0);
        if ($roundId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid round_id']);
            return;
        }

        $staffId = 0;
        $systemUser = 'automation';

        if (!$this->roundLockService->acquireLock($roundId, $staffId)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Could not acquire lock on round']);
            return;
        }

        $finished = $this->roundWorkflowService->finishRound($roundId, $staffId);

        if (!$finished) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Round finish failed; check workflow state']);
            return;
        }

        $this->logger->log(
            Logger::LEVEL_INFO,
            Logger::EVENT_SYSTEM,
            'Automation: Round finished via API',
            ['round_id' => $roundId, 'staff_id' => $staffId],
            $systemUser
        );

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Round finished successfully']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
```

### 2. Add Route to Config

**File**: `src/config/routes.php`

**Change**: Add route in POST section (around line 404, before final `],`):

```php
// AUTOMATION ONLY: Remove before production
'/api/automation/finish-round' => [
    'path' => '/api/automation/finish-round',
    'controller' => 'App\\Controllers\\RoundController',
    'method' => 'automationFinishRound'
],
```

### 3. Create Automation Script

**File**: `scripts/tw3-season-automation.sh`

See: [scripts/tw3-season-automation.sh](scripts/tw3-season-automation.sh)

This is the main orchestrator with all 6 stages.

---

## Setup & Execution

### Prerequisites

```bash
# 1. Ensure TW3 source is available
docker compose exec db mysql -u root -p${DB_PASSWORD} -e "SHOW DATABASES;" | grep TW3_replay_25_26

# 2. Ensure TW4 is running
curl -s http://localhost:8084 | head -20

# 3. Ensure jq is installed (for JSON parsing in Stage 6)
which jq
```

### Environment Configuration

Set these environment variables:

```bash
export AUTOMATION_TOKEN="tw4_auto_25_26_$(date +%s)"
export TW3_REPLAY_SEASON="25_26"
export TW3_REPLAY_DATABASE="TW3_replay_25_26"
export APP_BASE_URL="http://localhost:8084"
```

Or add to `.env`:

```
AUTOMATION_TOKEN=tw4_auto_25_26_1722128400
TW3_REPLAY_SEASON=25_26
TW3_REPLAY_DATABASE=TW3_replay_25_26
APP_BASE_URL=http://localhost:8084
```

### Test the Endpoint

Before running automation:

```bash
curl -X POST http://localhost:8084/?controller=round&action=automationfinishround \
  -H "X-Automation-Token: $AUTOMATION_TOKEN" \
  -H "Content-Type: application/json" \
  -v
```

Expected response (will fail until round exists, but should auth):
```json
{"success": false, "message": "No permanent round found"}
```

### Run Full Automation for One Round

```bash
cd /home/ned-bollard/TW4

bash scripts/tw3-season-automation.sh auto 9
```

---

## Process Workflow

### For Each Round

**Before automation:**
1. Review previous round results
2. Update team assignments if needed
3. Configure team haggle settings
4. Adjust any player data in TW4 if necessary

**During automation:**
```bash
bash scripts/tw3-season-automation.sh auto <round-number>
```

**After automation:**
1. Review the 6-stage output
2. Check compare results—should show 0 mismatches
3. Verify exported HTML reports in public/reports/
4. Proceed to next round

### Example: Full Season 25_26

```bash
#!/bin/bash

export AUTOMATION_TOKEN="your-token"
export TW3_REPLAY_SEASON="25_26"
export TW3_REPLAY_DATABASE="TW3_replay_25_26"

for round in {1..26}; do
    echo "=========================================="
    echo "Automating Round $round"
    echo "=========================================="
    
    bash scripts/tw3-season-automation.sh auto $round
    
    if [[ $? -ne 0 ]]; then
        echo "❌ Round $round FAILED"
        read -p "Continue? (y/n) " -n 1 -r
        echo
        [[ ! $REPLY =~ ^[Yy]$ ]] && break
    fi
    
    # Manual pause for user to review/configure
    echo ""
    echo "Round $round complete. Review results, update teams/config as needed."
    read -p "Press Enter when ready for next round..."
done

echo "✓ Season automation complete!"
```

---

## Troubleshooting

### "Unauthorized" (HTTP 401)

**Cause**: AUTOMATION_TOKEN mismatch

**Fix**:
```bash
# Verify token in environment
echo $AUTOMATION_TOKEN

# Check it's set in docker-compose or .env
grep AUTOMATION_TOKEN .env

# Restart app if you changed .env
docker compose restart app
```

### "No permanent round found" (HTTP 400)

**Cause**: TW4_live.round is empty or reset

**Fix**:
```bash
# Check if round exists
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "SELECT * FROM round;"

# If empty, start a round via UI first, or create manually
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "INSERT INTO round (row_id, season_year, number_round, workflow_step) 
       VALUES (1, '25_26', 1, 'card_entry_open');"
```

### "Round finish failed; check workflow state" (HTTP 500)

**Cause**: Workflow is not in expected state (usually not 'card_entry_open' or lock issue)

**Fix**:
```bash
# Check workflow state
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "SELECT workflow_step, locked_by_staff_id FROM round WHERE row_id = 1;"

# Reset to expected state if needed
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "UPDATE round SET workflow_step = 'card_entry_open', locked_by_staff_id = NULL WHERE row_id = 1;"

# Check app logs for detailed error
docker compose logs app | tail -50
```

### Course not found

**Cause**: Course name from TW3 doesn't match any in TW4_base.course_played

**Fix**:
```bash
# List courses in TW3
docker compose exec db mysql -u root -p${DB_PASSWORD} TW3_replay_25_26 \
  -e "SELECT DISTINCT name_course FROM hist_round;"

# List courses in TW4
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_base \
  -e "SELECT row_id, name_course FROM course_played LIMIT 10;"

# Either add missing course to TW4, or update script to use better pattern match
```

### Compare stage shows mismatches

**Cause**: Workflow didn't complete correctly, or data is inconsistent

**Review**:
```bash
# Check which type of mismatch
# From compare output, re-run specific check:

# Handicaps
docker compose exec db mysql -u root -p${DB_PASSWORD} \
  -e "SELECT player_identifier, roster.handicap FROM TW4_base.roster ..."

# Best-five
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "SELECT * FROM best_five_scores WHERE season_year = '25_26';"

# Eclectic
docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_live \
  -e "SELECT * FROM eclectic_scores WHERE season_year = '25_26';"
```

---

## Production Considerations

### Before Going Live

⚠️ **MUST REMOVE automation code before production deployment**

See: [AUTOMATION_PRODUCTION_REMOVAL.md](AUTOMATION_PRODUCTION_REMOVAL.md)

### What to Delete

1. `automationFinishRound()` method from `src/Controllers/RoundController.php`
2. `/api/automation/finish-round` route from `src/config/routes.php`
3. `AUTOMATION_TOKEN` from environment configuration
4. (Optional) Archive `scripts/tw3-season-automation.sh` and supporting docs

### Why Remove

- **Security**: Direct API bypass of normal UI workflow
- **Data integrity**: Automation could interfere with live scoring
- **Maintenance**: No business value once replication is complete
- **Code clarity**: Production should not have "remove me" features

### If You Need to Replicate Again

1. Restore code from Git history
2. Set up isolated automation environment (never against production DB)
3. Re-remove after use
4. Or: Move automation to separate microservice/script repository

---

## Key Files

| File | Purpose |
|------|---------|
| [scripts/tw3-season-automation.sh](scripts/tw3-season-automation.sh) | Main orchestrator (6 stages) |
| [scripts/tw3-round-replay.sh](scripts/tw3-round-replay.sh) | Original load/compare logic (used by stages 3 & 6) |
| [scripts/import-tw3-replay-source.sh](scripts/import-tw3-replay-source.sh) | Import TW3 into Docker (one-time setup) |
| [src/Controllers/RoundController.php](src/Controllers/RoundController.php) | PHP endpoint (automationFinishRound method) |
| [src/config/routes.php](src/config/routes.php) | Route config (automation route) |
| [docs/AUTOMATION_SETUP.md](docs/AUTOMATION_SETUP.md) | Setup & usage guide |
| [AUTOMATION_PRODUCTION_REMOVAL.md](AUTOMATION_PRODUCTION_REMOVAL.md) | Production removal checklist |
| [AUTOMATION_ENDPOINT_PATCH.md](AUTOMATION_ENDPOINT_PATCH.md) | Technical endpoint details |

---

## Key Learnings & Gotchas

### Column Names
- TW3 uses `hist_round.date_played` (NOT `history_date`)
- TW3 uses `hist_round.name_course` (NOT `course_name`)
- Watch for similar mismatches in other tables

### Course Lookup
- Automation uses `LIKE CONCAT('%', course_name, '%')` for fuzzy matching
- If course not found, round uses `NULL` for `course_played_id`
- Manual course assignment may be needed post-automation

### Handicap Synchronization
- MikeW in round 8: TW3 = 22, TW4 was 23 (from previous round finish)
- **Principle**: Reset TW4 handicaps to match TW3's start-of-round state before loading cards
- Automation does this in Stage 2: `update_players()`

### Tiebreaker Display Fix
- Round 7 showed "name order" for position 10 (alphabetical sort)
- Fixed in `SnapshotExportService.php` to use coin toss (deterministic CRC32)
- This ensures consistent tiebreaker across live and exported reports

### Validation Passes When Round is Finished
- Stages 1-3 run before round is finished
- Stage 4 via API actually finishes the round
- Stage 6 compare expects round to be in TW4_history, not TW4_live

---

## Contact & Questions

For questions on this process:
1. Check [AUTOMATION_SETUP.md](docs/AUTOMATION_SETUP.md) for detailed setup
2. Review [scripts/tw3-season-automation.sh](scripts/tw3-season-automation.sh) for implementation details
3. Check `AUTOMATION_PRODUCTION_REMOVAL.md` for production guidance
4. Search codebase for "AUTOMATION ONLY" comments marking temporary code

---

**Last Updated**: 2026-07-28  
**Author**: Automation Setup Session
