# Daily Standup Template (TW4)

Use this in a new VS Code chat session:

Prompt:
"Create my standup from recent chat history using this format:
Yesterday:
1. ...
2. ...
3. ...
Today:
1. ...
2. ...
3. ...
Blockers:
1. ...
2. ...
Keep it concise and action-focused."

---

## Fill-In Template

Date: YYYY-MM-DD

Yesterday:
1. 
2. 
3. 

Today:
1. 
2. 
3. 

Blockers:
1. 
2. 

Notes:
1. 
2. 

---

## Ultra-Short Variant (30 seconds)

Use this when you need a quick update:

Date: YYYY-MM-DD

Done:
1. 

Next:
1. 

Blocker:
1. None

Prompt:
"Summarize my recent chat history in 3 lines: Done, Next, Blocker. Keep each line to one sentence."

---

## Example From Last Session (2026-04-18)

Yesterday:
1. Fixed course creation flow to correctly support course + gender.
2. Changed duplicate validation to course+gender (not course-only).
3. Improved save feedback and flash message handling in course creation views.
4. Updated success text to include gender, for example: Successfully created all 18 holes for ovgc (Female).
5. Committed and pushed changes (commit 7efb66f).
6. Resolved GitHub push 403 root cause (account mismatch) and set up dual SSH identities.
7. Applied Docker-first VS Code setting to stop host PHP executable prompts.

Today:
1. Re-test create flow for both male and female entries on clean data.
2. Add or verify tests around course+gender duplicate logic and flash behavior.
3. Validate dual-account Git workflow on next push.

Blockers:
1. Git identity mismatch remains a recurring risk when switching work and personal contexts.
2. Some host-based VS Code extensions can still conflict with container-first tooling assumptions.

Notes:
1. Remote/auth consistency checks before push can prevent avoidable 403 errors.
2. Keep flash message display paths consistent across controller redirects and index rendering.
