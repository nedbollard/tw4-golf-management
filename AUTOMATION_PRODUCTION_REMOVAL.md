# ⚠️ AUTOMATION CODE - REMOVE BEFORE PRODUCTION

## Overview
This document tracks automation code added for **TW3-to-TW4 season replication during development**.

This code is **NOT for production use** and must be removed or isolated before going live.

## Files to Remove/Review

### 1. PHP Endpoint (RoundController)
**File**: `src/Controllers/RoundController.php`

**Method to DELETE**: `automationFinishRound()` (lines ~263–340)

**What it does**: 
- API endpoint for automated round finishing
- Accepts POST to `/api/automation/finish-round`
- Bypasses normal UI workflow

**Why remove**:
- Allows direct API access to critical workflow without user interaction
- Security risk if exposed in production
- Not needed after season replication is complete

---

### 2. Route Configuration
**File**: `src/config/routes.php`

**Route to DELETE**: `/api/automation/finish-round` (line ~404)

```php
// AUTOMATION ONLY: Remove before production
'/api/automation/finish-round' => [
    'path' => '/api/automation/finish-round',
    'controller' => 'App\\Controllers\\RoundController',
    'method' => 'automationFinishRound'
],
```

**Why remove**:
- Route only makes sense if endpoint exists
- No legitimate use case in production

---

### 3. Automation Script (Optional - can keep for future use)
**File**: `scripts/tw3-season-automation.sh`

**Decision**: Keep or archive depending on whether you'll replicate seasons again.

If removing:
- Delete the entire file
- Also delete `scripts/tw3-round-replay.sh` if no longer needed
- Delete supporting files: `AUTOMATION_ENDPOINT_PATCH.md`, `docs/AUTOMATION_SETUP.md`

If keeping:
- Isolate to a separate automation-only environment
- Do NOT use in production TW4
- Requires `AUTOMATION_TOKEN` environment variable (see below)

---

### 4. Environment Variable
**Location**: `.env` or `docker-compose.yml`

**Variable to REMOVE**: `AUTOMATION_TOKEN=...`

This variable should NOT exist in production configuration.

---

## Checklist for Production Deployment

- [ ] Delete `automationFinishRound()` method from RoundController
- [ ] Remove `/api/automation/finish-round` route from routes.php
- [ ] Delete `AUTOMATION_TOKEN` from environment configuration
- [ ] (Optional) Archive or remove `tw3-season-automation.sh` script
- [ ] Code review confirms no other automation endpoints remain
- [ ] Test deployment to verify normal UI workflow still works

---

## Context for Reviewers

**What was this?**
- Automated replication of TW3 season (25_26) into TW4
- 6-stage orchestration: start round → update players → load cards → finish round → set closest-to-pin → compare results
- Used during development to validate data consistency between TW3 and TW4

**When was it added?**
- Date: 2026-07-28
- Purpose: Development/testing for season replication
- Duration: Temporary, for bootstrapping TW4 with historical data

**Why remove before production?**
1. **Security**: Direct API bypass of normal UI workflow
2. **Data integrity**: Automation could interfere with live scoring
3. **Maintenance**: No business value once replication is complete
4. **Clarity**: Production code should not have "remove me" features

---

## Recovery / Restoration

If you need to replicate another season after production:

1. Check Git history to restore this code
2. Set up isolated automation environment
3. Never run against production database
4. Re-remove after use

---

**Questions?** Reference commit history or documentation:
- `docs/AUTOMATION_SETUP.md` — Setup & usage guide (also remove if not keeping script)
- `AUTOMATION_ENDPOINT_PATCH.md` — Technical details (also remove if not keeping script)
- Session memory: `/memories/session/tw4-auto-replay-design.md` — Design notes
