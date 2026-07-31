# TW4 Full Season Automation Setup & Usage

## Overview

Two-stage process to replicate an entire TW3 season into TW4:

1. **PHP Endpoint Setup** — Enable the automation API in RoundController
2. **Bash Automation Script** — Run `tw3-season-automation.sh` for each round

## Stage 1: PHP Endpoint Setup (One-time)

### 1.1 Add Endpoint to RoundController

Edit `src/Controllers/RoundController.php` and add the `automationFinishRound()` method before the final closing brace.

See: [AUTOMATION_ENDPOINT_PATCH.md](AUTOMATION_ENDPOINT_PATCH.md) for the exact code.

### 1.2 Update Router

Ensure your routing in `public/index.php` recognizes the automation action. Example:

```php
// In public/index.php router section
$route = strtolower($action);

// ... existing routes ...

if ($route === 'automationfinishround') {
    $controller->automationFinishRound();
    exit;
}
```

### 1.3 Set Environment Variable

Add `AUTOMATION_TOKEN` to your `.env` or `docker-compose.yml`:

```bash
# .env
AUTOMATION_TOKEN=tw4_auto_25_26_$(date +%s)

# or in docker-compose.yml
services:
  app:
    environment:
      - AUTOMATION_TOKEN=tw4_auto_25_26_$(date +%s)
```

### 1.4 Test the Endpoint

```bash
export AUTOMATION_TOKEN="your-token-value"

curl -X POST http://localhost:8084/?controller=round&action=automationfinishround \
  -H "X-Automation-Token: $AUTOMATION_TOKEN" \
  -H "Content-Type: application/json" \
  -v
```

Expected response (success):
```json
{"success": true, "message": "Round finished successfully"}
```

---

## Stage 2: Run Full Season Automation

### 2.1 Verify Prerequisites

- TW3 source database is available in Docker: `TW3_replay_25_26` (or your configured name)
- TW4 is running and healthy at http://localhost:8084
- Environment variable `AUTOMATION_TOKEN` is set and matches your endpoint
- `jq` is installed (for JSON parsing)

### 2.2 Manual Pre-Round Setup (One-time per Round)

Before each round automation:

1. **Start a new season/round in TW4 UI** (create empty round entry)
   - Or the script will update the existing permanent round

2. **Update teams/config between rounds** (manual step)
   - This is where you manage lineup changes, team haggle setup, etc.
   - Automation only handles: start round → load cards → finish round → validate

### 2.3 Run Full Automation for One Round

```bash
cd /home/ned-bollard/TW4

export AUTOMATION_TOKEN="your-token-value"
export TW3_REPLAY_SEASON="25_26"
export TW3_REPLAY_DATABASE="TW3_replay_25_26"
export APP_BASE_URL="http://localhost:8084"

bash scripts/tw3-season-automation.sh auto 8
```

### Output Example

```
TW4 Automation: Full Season Replication
========================================
Season: 25_26
Round: 8
Source: TW3_replay_25_26

=== STAGE 1: Starting Round 8 ===
  Round date: 2025-11-20
  Course ID: 1, Name: Ohariu Valley
✓ Round started in TW4

=== STAGE 2: Updating Players ===
  Creating new players:
    (none needed)
  Adjusting handicaps to start-of-round state:
    ✓ MikeW: 23 -> 22
✓ Players updated

=== STAGE 3: Loading Cards ===
mysql: [Warning] Using a password on the command line interface is insecure.
...
7
63
✓ Cards loaded (7 cards, 63 holes)

=== STAGE 4: Finishing Round via API ===
✓ Round finished via API (all workflows executed: handicaps, best-five, eclectic, export)

=== STAGE 5: Setting Closest to the Pin ===
  Inserting closest to pin records:
    ✓ Hole 3: ColinB
    ✓ Hole 6: KateB
✓ Closest to pin set

=== STAGE 6: Comparing Results Against TW3 ===
mysql: [Warning] Using a password on the command line interface is insecure.
mysql: [Warning] Using a password on the command line interface is insecure.
CHECK                    MISMATCHES
finished_history         0
handicap                 0
best_five                0
eclectic                 0
Round 8 matches TW3 for handicap, best five, and eclectic scores.

========================================
✓ AUTOMATION COMPLETE FOR ROUND 8
========================================
```

### Exit Codes

- **0**: Success — all stages passed, round validated
- **1**: Failure — one or more stages failed, check output above

---

## Workflow for Full Season Replication

For a complete season 25_26 → TW4:

```bash
#!/bin/bash

export AUTOMATION_TOKEN="your-token"
export TW3_REPLAY_SEASON="25_26"
export TW3_REPLAY_DATABASE="TW3_replay_25_26"

# Round 1
bash scripts/tw3-season-automation.sh auto 1
# (Manual: review results, update teams/config as needed)

# Round 2
bash scripts/tw3-season-automation.sh auto 2
# (Manual: review, update teams)

# ... repeat for all rounds in season ...

# Round 26
bash scripts/tw3-season-automation.sh auto 26
```

**Between each round**, you manually:
1. Review the automation output and results
2. Update player lineups, team assignments, team haggle setup
3. Verify config is ready for next round
4. Trigger next automation run

---

## Troubleshooting

### "AUTOMATION_TOKEN environment variable not set"

```bash
export AUTOMATION_TOKEN="your-token-value"
# Verify:
echo $AUTOMATION_TOKEN
```

### "Finish round API returned HTTP 401"

- Token mismatch between script and `.env` / `docker-compose.yml`
- Ensure both are using the exact same value
- Restart app container if you changed `.env`:
  ```bash
  docker compose restart app
  ```

### "Finish round API returned HTTP 500"

- Round is not in the correct workflow state
- Check TW4_live.round workflow_step is `card_entry_open`
- Check logs in the app container:
  ```bash
  docker compose logs app | tail -50
  ```

### "Course not found"

- The script uses course name pattern matching
- If course is NULL in TW4_live.round, check course_played table:
  ```bash
  docker compose exec db mysql -u root -p${DB_PASSWORD} TW4_base -e \
    "SELECT row_id, name_course FROM course_played LIMIT 10;"
  ```
- Update script with correct course name pattern or course_played_id

### Players created with wrong names

- Script splits player_identifier (e.g., "JonG" → "Jon" + "G")
- If this is wrong, manually update in TW4_base.roster post-automation
- Or submit PR to improve player name detection

---

## Architecture

```
tw3-season-automation.sh (bash orchestrator)
├── Stage 1: Create/update TW4_live.round (metadata from TW3)
├── Stage 2: Player sync (create new, adjust handicaps)
├── Stage 3: Load cards (transactional insert, existing logic)
├── Stage 4: Finish round API call
│   └── Calls PHP endpoint → RoundWorkflowService::finishRound()
│       ├── Apply handicap updates
│       ├── Refresh best-five scores
│       ├── Refresh eclectic calculations
│       ├── Create history snapshot
│       ├── Export HTML snapshots
│       └── Update workflow state
├── Stage 5: Set closest to pin (from TW3 hist_result)
└── Stage 6: Validate against TW3 (compare script)
```

**Why This Matters**:
- Stage 4 calls **real production code** (no fakes)
- Full test of workflow: handicaps, best-five, eclectic, export, history
- Validation confirms TW4 replication matches TW3 exactly

---

## Testing Single Round (Manual Full Cycle)

To test round 8 without full automation:

```bash
# 1. Preflight check
bash scripts/tw3-round-replay.sh preflight 8

# 2. Load cards only
bash scripts/tw3-round-replay.sh load 8

# 3. Manually click "Finish Round" in UI or via automation script

# 4. Compare
bash scripts/tw3-round-replay.sh compare 8
```

Or run full automation:

```bash
bash scripts/tw3-season-automation.sh auto 8
```

---

## Next Steps

1. Add PHP endpoint (see AUTOMATION_ENDPOINT_PATCH.md)
2. Set AUTOMATION_TOKEN in environment
3. Test endpoint with curl (see 1.4 above)
4. Run `tw3-season-automation.sh auto 8` to validate round 8
5. If successful, proceed with remaining rounds
