# TW4 User Guide

## Purpose
This guide is for a prospective user or tester of the TW4 Twilight Golf Scoring application.

It explains what each role does, where to click, and what outcomes to expect.

## What TW4 Does
TW4 manages a twilight golf competition workflow:
1. Configure competition settings.
2. Maintain staff, course, and roster data.
3. Run round scoring in a controlled step sequence.
4. Present results and complete the round.

## Accessing The Application
Use your deployment URL. Typical examples:
1. Local development: http://localhost:8084
2. Oracle host example: http://<server-ip>:8084

Primary entry points:
1. Home: /
2. Login: /login

## Roles
TW4 has two operational roles:
1. Admin
2. Scorer

### Admin
Admin can:
1. Configure system settings.
2. Manage course definitions.
3. Manage playable course definitions.
4. Manage staff accounts.
5. Manage scoring-state recovery actions.

### Scorer
Scorer can:
1. Prepare roster data.
2. Run the scoring workflow in sequence.
3. Enter cards and hole scores.
4. Present results.
5. Finish round.

## Sign-In
1. Open /login.
2. Enter username and password.
3. Successful login redirects to role menu:
   1. Admin to /admin/menu
   2. Scorer to /scorer/menu

If credentials fail, TW4 shows an invalid login message and keeps you on /login.

## Admin Guide
Admin menu route: /admin/menu

### 1. Configure System
Route: /config

Set and review application values such as:
1. Club name
2. Competition name
3. Season year
4. Competition settings used by scoring logic

### 2. Course Club Management
Route: /course-club

Use this to maintain course and hole metadata used in scoring.

### 3. Course Played Management
Route: /course-played

Use this to define playable course layouts used for rounds.

### 4. Staff Management
Route: /staff

Use this to:
1. Add staff (/staff/add)
2. Edit staff (/staff/edit/{id})
3. Activate/deactivate operational users as needed

### 5. Scoring State Management
Route: /admin/scoring-state

This page provides operational recovery controls:
1. Unlock Scoring Process
2. Reset Results Complete to Cards Entry Open

Expected behavior:
1. Reset is only enabled when workflow step is results_presented.
2. Reset clears TW4_live.results.

## Scorer Guide
Scorer menu routes:
1. /scorer/menu
2. /scorer

The scorer screen enforces a step-based process.

### Workflow Steps
Typical sequence:
1. Start Round
2. Enter Cards
3. Present Results
4. Finish Round

Expected behavior:
1. Steps unlock in order.
2. Only valid next actions are enabled.
3. Score lock prevents simultaneous conflicting scoring actions.

### Roster
Route: /roster

Use this page before scoring if player list changes are needed.

### Enter Cards
Route: /scores/enter

Use this to capture card-level and hole-level scores.

### Present Results
Route: /scores/present-results

Use this after cards are entered. The workflow can enforce minimum card requirements.

### View Outputs
Common routes:
1. Leaderboard: /leaderboard
2. Results: /results

## First-Time Tester Walkthrough
Use this sequence for a clean functional pass.

### Pass A: Admin Setup
1. Login as admin.
2. Open /config and confirm required values exist.
3. Open /course-club and verify holes exist.
4. Open /course-played and verify at least one playable setup exists.
5. Open /staff and confirm at least one admin and one scorer account.

### Pass B: Scorer Round Flow
1. Login as scorer.
2. Open /roster and confirm players.
3. Open /scorer/menu.
4. Execute Start Round.
5. Execute Enter Cards.
6. Execute Present Results.
7. Execute Finish Round.

### Pass C: Admin Recovery Control Check
1. Login as admin.
2. Open /admin/scoring-state.
3. Verify state information appears.
4. Use reset/unlock controls only when scenario requires.

## Operational Notes
1. Logout route is /logout.
2. Role switch helpers exist at /switch/admin and /switch/scorer for authenticated staff.
3. Error page route is /error.

## Troubleshooting
### Login fails
1. Verify username exists and account is active.
2. Verify password is correct.
3. Check app logs for authentication errors.

### Scorer cannot proceed
1. Open scorer menu and inspect which step is enabled.
2. Confirm a lock is not held by another scorer.
3. If required, ask admin to use /admin/scoring-state.

### Results reset unavailable
1. Reset action is enabled only in results_presented state.
2. This is expected behavior, not a defect.

## Acceptance Checklist For External Review
1. Admin can sign in and open /admin/menu.
2. Scorer can sign in and open /scorer/menu.
3. Workflow progression is enforced in order.
4. Present Results and Finish Round complete successfully.
5. Admin scoring-state controls behave as documented.
6. Logout returns user to public entry flow.

## Document Version
Version: 1.0
Date: 2026-05-31
Scope: User-facing operation guide for current TW4 routes and workflows.
