<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\HandicapReferenceService;

class HandicapReferenceController extends BaseController
{
    public function __construct(
        Application $app,
        private HandicapReferenceService $handicapReferenceService
    ) {
        parent::__construct($app);
    }

    public function index(): void
    {
        $this->requireRole('scorer');

        $clubNumber = (int) $this->configService->getConfigValue('club_number', '294');
        $tees = $this->handicapReferenceService->getCurrentTees($clubNumber);
        $gender = strtoupper((string) ($_GET['gender'] ?? 'M'));
        $teeId = (int) ($_GET['tee_id'] ?? 0);
        $indexType = (string) ($_GET['index_type'] ?? 'standard');
        $indexValue = trim((string) ($_GET['handicap_index'] ?? ''));
        $errors = [];
        $result = null;
        $selectedTee = null;

        if (isset($_GET['calculate'])) {
            if (!in_array($gender, ['M', 'F'], true)) {
                $errors[] = 'Select a valid gender.';
            }
            if (!in_array($indexType, ['standard', 'plus'], true)) {
                $errors[] = 'Select a valid index type.';
            }
            if ($indexValue === '' || !is_numeric($indexValue)) {
                $errors[] = 'Enter a valid Handicap Index.';
            } elseif ((float) $indexValue < 0 || (float) $indexValue > 54) {
                $errors[] = 'Handicap Index must be between 0.0 and 54.0.';
            }

            if ($teeId > 0) {
                $selectedTee = $this->handicapReferenceService->getTee($teeId, $clubNumber);
            }
            if ($selectedTee === null || (string) ($selectedTee['gender'] ?? '') !== $gender) {
                $errors[] = 'Select a tee for the chosen gender.';
            }

            if ($errors === []) {
                $result = $this->handicapReferenceService->calculate(
                    (float) $indexValue,
                    $indexType === 'plus',
                    $selectedTee
                );
            }
        }

        $this->render('handicap/reference', [
            'clubNumber' => $clubNumber,
            'tees' => $tees,
            'gender' => $gender,
            'teeId' => $teeId,
            'indexType' => $indexType,
            'indexValue' => $indexValue,
            'selectedTee' => $selectedTee,
            'result' => $result,
            'errors' => $errors,
        ]);
    }
}