<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;

/**
 * Round Controller - Handle round-related operations
 */
class RoundController extends BaseController
{
    private Logger $logger;
    private RoundWorkflowService $roundWorkflowService;
    private RoundLockService $roundLockService;

    public function __construct(
        Application $app,
        Logger $logger,
        RoundWorkflowService $roundWorkflowService,
        RoundLockService $roundLockService
    )
    {
        parent::__construct($app);
        $this->logger = $logger;
        $this->roundWorkflowService = $roundWorkflowService;
        $this->roundLockService = $roundLockService;
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('rounds/index', [
            'title' => 'Rounds - TW4 Golf Management'
        ]);
    }

    public function start(): void
    {
        $this->requireRole('scorer');
        $this->requireScoringConfigReady('/scorer/menu');

        $round = $this->roundWorkflowService->getPermanentRound();

        if (!in_array((string) ($round['workflow_step'] ?? 'between_rounds'), ['between_rounds', 'not_started'], true)) {
            $this->flash->error('Round can only be started when workflow_step is between_rounds.');
            $this->redirect('/scorer/menu');
        }

        $formData = $this->roundWorkflowService->getStartRoundFormData();

        $this->render('rounds/start', [
            'title' => 'Start Round - TW4 Golf Management',
            'formData' => $formData
        ]);
    }

    public function store(): void
    {
        $this->requireRole('scorer');
        $this->requireScoringConfigReady('/scorer/menu');

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/rounds/start');
            return;
        }

        $user = $this->authService->getUser();
        $round = $this->roundWorkflowService->getPermanentRound();

        if (!in_array((string) ($round['workflow_step'] ?? 'between_rounds'), ['between_rounds', 'not_started'], true)) {
            $this->flash->error('Round can only be started when workflow_step is between_rounds.');
            $this->redirect('/scorer/menu');
        }

        $formData = $this->roundWorkflowService->getStartRoundFormData();
        $postData = $this->getPostData();
        $allowedCourseIds = array_map(
            static fn(array $course): int => (int) $course['row_id'],
            $formData['courses']
        );

        $errors = [];
        $roundDate = $this->normalizeRoundDateInput((string) ($postData['round_date'] ?? ''));
        $postData['round_date'] = $roundDate;
        $roundNumber = isset($postData['round_number']) ? (int) $postData['round_number'] : 0;
        $coursePlayedId = isset($postData['course_played_id']) ? (int) $postData['course_played_id'] : 0;

        if ($roundDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $roundDate) !== 1) {
            $errors['round_date'] = 'Round date is required in format dd/mm/yyyy.';
        }

        if ($roundNumber < 1) {
            $errors['round_number'] = 'Round number must be at least 1.';
        }

        if ($coursePlayedId < 1 || !in_array($coursePlayedId, $allowedCourseIds, true)) {
            $errors['course_played_id'] = 'Please select a valid course.';
        }

        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->flash->setOld($postData);
            $this->redirect('/rounds/start');
        }

        $beforeState = $this->roundWorkflowService->getPermanentRound();

        try {
            $this->roundWorkflowService->startRound($postData, (int) ($user['user_id'] ?? 0));
        } catch (\RuntimeException $e) {
            $this->flash->error($e->getMessage());
            $this->flash->setOld($postData);
            $this->redirect('/rounds/start');
            return;
        }

        $afterState = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, season_year, workflow_step, number_round, round_date, course_played_id FROM TW4_live.round WHERE row_id = ?',
            [(int) ($beforeState['round_id'] ?? 0)]
        );

        $this->logger->log(
            Logger::LEVEL_INFO,
            Logger::EVENT_SYSTEM,
            'Scoring workflow changed to card_entry_open (round started, state applied)',
            [
                'round_id' => (int) ($postData['round_id'] ?? $beforeState['round_id'] ?? 0),
                'staff_id' => (int) ($user['user_id'] ?? 0),
                'before_workflow_step' => (string) ($beforeState['workflow_step'] ?? 'unknown'),
                'after_workflow_step' => (string) ($afterState['workflow_step'] ?? 'unknown'),
                'season_year' => (string) ($afterState['season_year'] ?? ''),
                'round_number' => (int) ($afterState['number_round'] ?? 0),
                'round_date' => $afterState['round_date'] ?? null,
                'course_played_id' => (int) ($afterState['course_played_id'] ?? 0),
            ],
            (string) ($user['username'] ?? 'system')
        );

        $this->redirect('/scorer/menu');
    }

    public function finish(): void
    {
        $this->requireRole('scorer');
        $this->requireScoringConfigReady('/scorer/menu');

        $user = $this->authService->getUser();
        $staffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');
        $active = $this->roundWorkflowService->getActiveRoundForScorerMenu();

        if (!$active) {
            $this->flash->error('No active round found to finish.');
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) ($active['round_id'] ?? 0);

        if (!$this->roundLockService->assertLockHeld($roundId, $staffId)
            && !$this->roundLockService->acquireLock($roundId, $staffId)) {
            $this->flash->error('Unable to acquire the scoring lock to finish the round.');
            $this->redirect('/scorer/menu');
            return;
        }

        $beforeState = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, workflow_step, card_count FROM TW4_live.round WHERE row_id = ?',
            [$roundId]
        );

        try {
            $finished = $this->roundWorkflowService->finishRound($roundId, $staffId);
        } catch (\Throwable $e) {
            $this->flash->error($e->getMessage());
            $this->redirect('/scorer/menu');
            return;
        }

        if (!$finished) {
            $this->flash->error('Round could not be finished. Ensure workflow is results_presented and your lock is active.');
            $this->redirect('/scorer/menu');
            return;
        }

        $afterState = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, workflow_step, card_count, finished_at FROM TW4_live.round WHERE row_id = ?',
            [$roundId]
        );

        $this->logger->log(
            Logger::LEVEL_INFO,
            Logger::EVENT_SYSTEM,
            'Scoring workflow changed to between_rounds (round finished, state applied)',
            [
                'round_id' => $roundId,
                'staff_id' => $staffId,
                'before_workflow_step' => (string) ($beforeState['workflow_step'] ?? 'unknown'),
                'before_card_count' => (int) ($beforeState['card_count'] ?? 0),
                'after_workflow_step' => (string) ($afterState['workflow_step'] ?? 'unknown'),
                'after_card_count' => (int) ($afterState['card_count'] ?? 0),
                'finished_at' => $afterState['finished_at'] ?? null,
            ],
            $username
        );

        // Export is now handled within RoundWorkflowService::finishRound() before course_played_id is reset
        $_SESSION['just_finished_round'] = true;
        $this->flash->success('Round finished. Workflow set to between_rounds and roster statuses reset to active.');

        $this->redirect('/scorer/menu');
    }

    public function forceUnlock(string $id): void
    {
        $this->requireRole('admin');

        $user = $this->authService->getUser();
        $this->roundLockService->forceReleaseLock((int) $id, (int) ($user['user_id'] ?? 0), 'admin_forced');

        $this->redirect('/scorer/menu');
    }

    private function normalizeRoundDateInput(string $input): string
    {
        $value = trim($input);
        if ($value === '') {
            return '';
        }

        // Allow users to type local format while storing ISO date.
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches) === 1) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return '';
        }

        // Keep compatibility with native date inputs that submit ISO.
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            return checkdate($month, $day, $year) ? $value : '';
        }

        return '';
    }

    /**
     * AUTOMATION ONLY: API endpoint for season replication
     * Start a new round via automated workflow
     *
     * POST /api/automation/start-round
     * Requires: X-Automation-Token header, JSON body with round metadata
     * Body: {season_year, number_round, round_date, course_played_id}
     * Returns: JSON {success: bool, message: string}
     *
     * TO PRODUCTION REVIEWER: DELETE THIS METHOD before going live
     */
    public function automationStartRound(): void
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
            // Parse JSON body
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
                return;
            }

            // Extract payload
            $payload = [
                'round_number' => $input['number_round'] ?? null,
                'round_date' => $input['round_date'] ?? null,
                'course_played_id' => $input['course_played_id'] ?? null,
            ];

            // Use system automation user
            $staffId = 0;
            $systemUser = 'automation';

            // Force release any existing locks from automation user before starting
            $this->roundLockService->releaseAnyLocksByStaff($staffId, 'logout');

            // Call startRound with full workflow execution
            // (startRound acquires and releases its own lock internally)
            $result = $this->roundWorkflowService->startRound($payload, $staffId);

            if (!$result || empty($result['round_id'])) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Round start failed']);
                return;
            }

            // Log the automation start
            $this->logger->log(
                Logger::LEVEL_INFO,
                Logger::EVENT_SYSTEM,
                'Automation: Round started via API',
                ['round_id' => $result['round_id'], 'staff_id' => $staffId],
                $systemUser
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Round started successfully',
                'round' => [
                    'round_id' => $result['round_id'],
                    'number_round' => $result['number_round'] ?? null,
                    'workflow_step' => $result['workflow_step'] ?? 'card_entry_open'
                ]
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * AUTOMATION ONLY: API endpoint for season replication
     * Finish the current round via automated workflow
     *
     * POST /api/automation/finish-round
     * Requires: X-Automation-Token header
     * Returns: JSON {success: bool, message: string}
     *
     * TO PRODUCTION REVIEWER: DELETE THIS METHOD before going live
     */
    // AUTOMATION ONLY: Remove before production
    public function automationPresentResults(): void
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

            // Force release any existing locks from automation user
            $this->roundLockService->releaseAnyLocksByStaff($staffId, 'logout');

            // Acquire fresh lock for automation
            if (!$this->roundLockService->acquireLock($roundId, $staffId)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Could not acquire lock on round']);
                return;
            }

            // Call presentResults to transition from card_entry_open to results_presented
            $presented = $this->roundWorkflowService->presentResults($roundId, $staffId);

            // Release lock now that operation is complete
            $this->roundLockService->releaseLock($roundId, $staffId, 'finished');

            if (!$presented) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Results presentation failed; check workflow state']);
                return;
            }

            // Log the automation presentation
            $this->logger->log(
                Logger::LEVEL_INFO,
                Logger::EVENT_SYSTEM,
                'Automation: Results presented via API',
                ['round_id' => $roundId, 'staff_id' => $staffId],
                $systemUser
            );

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Results presented successfully']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

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

            // Force release any existing locks from automation user
            $this->roundLockService->releaseAnyLocksByStaff($staffId, 'logout');

            // Acquire lock for automation
            if (!$this->roundLockService->acquireLock($roundId, $staffId)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Could not acquire lock on round']);
                return;
            }

            // Call finishRound with full workflow execution
            $finished = $this->roundWorkflowService->finishRound($roundId, $staffId);

            // Release lock now that operation is complete
            $this->roundLockService->releaseLock($roundId, $staffId, 'finished');

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
}