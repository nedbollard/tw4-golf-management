<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\CoursePlayedService;
use App\Services\Logger;

class CoursePlayedController extends BaseController
{
    private Logger $logger;
    private CoursePlayedService $coursePlayedService;

    public function __construct(Application $app, Logger $logger, CoursePlayedService $coursePlayedService)
    {
        parent::__construct($app);
        $this->logger = $logger;
        $this->coursePlayedService = $coursePlayedService;
    }

    public function index(): void
    {
        $this->requireRole('admin');

        $service = $this->coursePlayedService;
        $coursesPlayed = $service->getAllCoursesPlayed();

        $this->render('course-played/index', [
            'coursesPlayed' => $coursesPlayed,
            'user' => $this->authService->getUser(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin');

        $service = $this->coursePlayedService;
        $clubs = $service->getUniqueClubNames();

        $this->render('course-played/form', [
            'mode' => 'create',
            'coursePlayed' => null,
            'holes' => [],
            'clubs' => $clubs,
            'user' => $this->authService->getUser(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin');

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/course-played/create');
            return;
        }

        $data = $this->getPostData();
        [$errors, $numberHoles] = $this->validateCoursePlayedInput($data);

        $service = $this->coursePlayedService;
        if (empty($errors) && $service->courseExistsForClub(trim($data['name_course'] ?? ''), trim($data['name_club'] ?? ''))) {
            $errors[] = 'This course name already exists for the selected club.';
        }

        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->flash->setOld($data);
            $this->redirect('/course-played/create');
            return;
        }

        $username = $this->authService->getUser()['username'] ?? 'system';
        $ok = $service->createCoursePlayed([
            'name_course' => trim($data['name_course']),
            'name_club' => trim($data['name_club']),
            'ident_eclectic' => trim($data['ident_eclectic']),
        ], $numberHoles, $username);

        if ($ok) {
            $this->flash->success('Course Played created successfully.');
            $this->redirect('/course-played');
            return;
        }

        $this->flash->error('Failed to create Course Played.');
        $this->flash->setOld($data);
        $this->redirect('/course-played/create');
    }

    public function edit(int $id): void
    {
        $this->requireRole('admin');

        $service = $this->coursePlayedService;
        $coursePlayed = $service->getCoursePlayedById($id);
        if (!$coursePlayed) {
            $this->flash->error('Course Played not found.');
            $this->redirect('/course-played');
            return;
        }

        $holes = $service->getCoursePlayedHoles($id);
        $clubs = $service->getUniqueClubNames();

        $this->render('course-played/form', [
            'mode' => 'edit',
            'coursePlayed' => $coursePlayed,
            'holes' => $holes,
            'clubs' => $clubs,
            'user' => $this->authService->getUser(),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('admin');

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/course-played/' . $id . '/edit');
            return;
        }

        $service = $this->coursePlayedService;
        $coursePlayed = $service->getCoursePlayedById($id);
        if (!$coursePlayed) {
            $this->flash->error('Course Played not found.');
            $this->redirect('/course-played');
            return;
        }

        $data = $this->getPostData();
        [$errors, $numberHoles] = $this->validateCoursePlayedInput($data);

        if (empty($errors) && $service->courseExistsForClub(trim($data['name_course'] ?? ''), trim($data['name_club'] ?? ''), $id)) {
            $errors[] = 'This course name already exists for the selected club.';
        }

        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->flash->setOld($data);
            $this->redirect('/course-played/' . $id . '/edit');
            return;
        }

        $username = $this->authService->getUser()['username'] ?? 'system';
        $ok = $service->updateCoursePlayed($id, [
            'name_course' => trim($data['name_course']),
            'name_club' => trim($data['name_club']),
            'ident_eclectic' => trim($data['ident_eclectic']),
        ], $numberHoles, $username);

        if ($ok) {
            $this->flash->success('Course Played updated successfully.');
            $this->redirect('/course-played');
            return;
        }

        $this->flash->error('Failed to update Course Played.');
        $this->flash->setOld($data);
        $this->redirect('/course-played/' . $id . '/edit');
    }

    public function delete(int $id): void
    {
        $this->requireRole('admin');

        $username = $this->authService->getUser()['username'] ?? 'system';
        $ok = $this->coursePlayedService->deleteCoursePlayed($id, $username);

        if ($ok) {
            $this->flash->success('Course Played deleted successfully.');
        } else {
            $this->flash->error('Failed to delete Course Played.');
        }

        $this->redirect('/course-played');
    }

    private function validateCoursePlayedInput(array $data): array
    {
        $errors = [];

        $nameCourse = trim($data['name_course'] ?? '');
        $nameClub = trim($data['name_club'] ?? '');
        $identEclectic = trim($data['ident_eclectic'] ?? '');

        if ($nameCourse === '') {
            $errors[] = 'Course name is required.';
        } elseif (strlen($nameCourse) > 16) {
            $errors[] = 'Course name must be 16 characters or less.';
        }

        if ($nameClub === '') {
            $errors[] = 'Club name is required.';
        } elseif (strlen($nameClub) > 16) {
            $errors[] = 'Club name must be 16 characters or less.';
        }

        if ($identEclectic === '') {
            $errors[] = 'Eclectic identifier is required.';
        } elseif (strlen($identEclectic) > 16) {
            $errors[] = 'Eclectic identifier must be 16 characters or less.';
        }

        $holes = $data['holes'] ?? [];
        $numberHoles = [];

        for ($position = 1; $position <= 9; $position++) {
            $numberHole = $holes[$position] ?? null;
            if ($numberHole === null || $numberHole === '' || !is_numeric($numberHole)) {
                $errors[] = "Selection {$position}: club hole is required.";
                continue;
            }

            $numberHole = (int) $numberHole;
            if ($numberHole < 1 || $numberHole > 18) {
                $errors[] = "Selection {$position}: club hole must be between 1 and 18.";
                continue;
            }

            $numberHoles[$position] = $numberHole;
        }

        if (count(array_unique($numberHoles)) !== count($numberHoles)) {
            $errors[] = 'Each selected club hole must be unique within the 9-hole course.';
        }

        return [$errors, $numberHoles];
    }
}
