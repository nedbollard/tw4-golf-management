# Add Automation Endpoint to RoundController

## File: src/Controllers/RoundController.php

Add the following method before the final closing brace (line 260):

```php
    /**
     * API endpoint for automation: finish the current round
     * POST /api/automation/finish-round
     * Requires: X-Automation-Token header
     * Returns: JSON {success: bool, message: string}
     */
    public function automationFinishRound(): void
    {
        header('Content-Type: application/json');

        // Verify automation token (simple security check)
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

            // Use system automation user (staff_id = 0)
            $staffId = 0;
            $systemUser = 'automation';

            // Acquire lock for automation
            if (!$this->roundLockService->acquireLock($roundId, $staffId)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Could not acquire lock on round']);
                return;
            }

            // Call finishRound with full workflow execution
            $finished = $this->roundWorkflowService->finishRound($roundId, $staffId);

            if (!$finished) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Round finish failed; check workflow state']);
                return;
            }

            // Log the automation finish
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

## Routing Setup

Ensure your router in `public/index.php` recognizes the action:
```php
$route = strtolower($action);
// ... existing routes ...
if ($route === 'automationfinishround') {
    $controller->automationFinishRound();
    exit;
}
```

Or use your router pattern if different.

## Environment Setup

Add to `.env` or docker-compose environment:
```
AUTOMATION_TOKEN=your-secure-random-token-here
```

Example docker-compose update:
```yaml
app:
  environment:
    - AUTOMATION_TOKEN=tw4_automation_25_26_$(date +%s)
```

## Testing the Endpoint

```bash
AUTOMATION_TOKEN="your-token"
curl -X POST http://localhost:8084/?controller=round&action=automationfinishround \
  -H "X-Automation-Token: $AUTOMATION_TOKEN" \
  -H "Content-Type: application/json" \
  -v
```

Expected response (success):
```json
{"success": true, "message": "Round finished successfully"}
```

Expected response (error):
```json
{"success": false, "message": "Round finish failed; check workflow state"}
```
