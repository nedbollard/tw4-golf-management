<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;

/**
 * Round Controller - Handle round-related operations
 */
class RoundController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
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

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $round = $workflow->getPermanentRound();

        if (($round['workflow_step'] ?? 'not_started') !== 'not_started') {
            $_SESSION['errors'] = ['round' => 'Round can only be started when workflow_step is not_started.'];
            $this->redirect('/scorer/menu');
        }

        $formData = $workflow->getStartRoundFormData();

        $this->render('rounds/start', [
            'title' => 'Start Round - TW4 Golf Management',
            'formData' => $formData,
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
        ]);

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function store(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $round = $workflow->getPermanentRound();

        if (($round['workflow_step'] ?? 'not_started') !== 'not_started') {
            $_SESSION['errors'] = ['round' => 'Round can only be started when workflow_step is not_started.'];
            $this->redirect('/scorer/menu');
        }

        $formData = $workflow->getStartRoundFormData();
        $postData = $this->getPostData();
        $allowedCourseIds = array_map(
            static fn(array $course): int => (int) $course['row_id'],
            $formData['courses']
        );

        $errors = [];
        $roundDate = $postData['round_date'] ?? '';
        $roundNumber = isset($postData['round_number']) ? (int) $postData['round_number'] : 0;
        $coursePlayedId = isset($postData['course_played_id']) ? (int) $postData['course_played_id'] : 0;

        if ($roundDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $roundDate) !== 1) {
            $errors['round_date'] = 'Round date is required.';
        }

        if ($roundNumber < 1) {
            $errors['round_number'] = 'Round number must be at least 1.';
        }

        if ($coursePlayedId < 1 || !in_array($coursePlayedId, $allowedCourseIds, true)) {
            $errors['course_played_id'] = 'Please select a valid course.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $postData;
            $this->redirect('/rounds/start');
        }

        $workflow->startRound($postData, (int) ($user['user_id'] ?? 0));

        $this->redirect('/scorer/menu');
    }

    public function finish(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if ($active) {
            $workflow->finishRound((int) $active['round_id'], (int) ($user['user_id'] ?? 0));
        }

        $this->redirect('/scorer/menu');
    }

    public function forceUnlock(string $id): void
    {
        $this->requireRole('admin');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $lockService = new RoundLockService($this->app->getDatabase());
        $lockService->forceReleaseLock((int) $id, (int) ($user['user_id'] ?? 0), 'admin_forced');

        $this->redirect('/scorer/menu');
    }
}