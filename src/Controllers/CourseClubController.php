<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\CourseClubService;
use App\Services\Logger;

class CourseClubController extends BaseController
{
    private Logger $logger;

    public function __construct(Application $app, Logger $logger)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }

    /**
     * Get CourseClubService instance
     */
    private function getCourseClubService(): CourseClubService
    {
        return new CourseClubService($this->app->getDatabase(), $this->logger);
    }

    /**
     * Display all course club holes
     */
    public function index(string $club = null, string $gender = null): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $courseClubs = $courseClubService->getAllCourseClubs();
        $clubNames = $courseClubService->getUniqueClubNames();
        
        // Pre-select filters if provided in URL
        $selectedClub = $club;
        $selectedGender = $gender;
        
        // Get pending edits from session
        $pendingEdits = $_SESSION['pendingEdits'] ?? [];
        
        $this->render('course-club/index', [
            'courseClubs' => $courseClubs,
            'clubNames' => $clubNames,
            'selectedClub' => $selectedClub,
            'selectedGender' => $selectedGender,
            'pendingEdits' => $pendingEdits,
            'user' => $this->app->getDatabase()->getAuth()->getUser()
        ]);
    }

    /**
     * Display create form
     */
    public function create(): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $clubNames = $courseClubService->getUniqueClubNames();
        $newCourse = $_GET['course'] ?? $_SESSION['newCourseAdded'] ?? null;
        
        // Clear the one-time new course session variable
        if (isset($_SESSION['newCourseAdded'])) {
            unset($_SESSION['newCourseAdded']);
        }
        
        $this->render('course-club/create', [
            'clubNames' => $clubNames,
            'newCourse' => $newCourse,
            'user' => $this->app->getDatabase()->getAuth()->getUser()
        ]);
    }

    /**
     * Store new course club hole
     */
    public function store(): void
    {
        $this->requireRole('admin');
        
        $data = $this->getPostData();
        $errors = $this->validateCourseClubData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/course-club/create');
            return;
        }

        $courseClubService = $this->getCourseClubService();
        
        // Check if hole number already exists for this club
        if ($courseClubService->holeNumberExists($data['name_club'], (int) $data['number_hole'])) {
            $_SESSION['errors'] = ['number_hole' => 'Hole number already exists for this club'];
            $_SESSION['old'] = $data;
            $this->redirect('/course-club/create');
            return;
        }

        $user = $this->app->getDatabase()->getAuth()->getUser();
        
        $courseClub = new \App\Models\CourseClub(
            $data['name_club'],
            (int) $data['number_hole'],
            $data['name_hole'],
            $data['gender'],
            (int) $data['par'],
            (int) $data['stroke'],
            $user['username']
        );

        if ($courseClubService->createCourseClub($courseClub)) {
            $_SESSION['success'] = 'Course hole created successfully';
            $this->redirect('/course-club');
        } else {
            $_SESSION['errors'] = ['create' => 'Failed to create course hole'];
            $this->redirect('/course-club/create');
        }
    }

    /**
     * Display edit form
     */
    public function edit(int $id): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $courseClub = $courseClubService->getCourseClubById($id);
        if (!$courseClub) {
            $_SESSION['errors'] = ['not_found' => 'Course hole not found'];
            $this->redirect('/course-club');
            return;
        }

        $clubNames = $courseClubService->getUniqueClubNames();
        
        $this->render('course-club/edit', [
            'courseClub' => $courseClub,
            'clubNames' => $clubNames,
            'user' => $this->app->getDatabase()->getAuth()->getUser()
        ]);
    }

    /**
     * Update course club hole
     */
    public function update(int $id): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $courseClub = $courseClubService->getCourseClubById($id);
        if (!$courseClub) {
            $_SESSION['errors'] = ['not_found' => 'Course hole not found'];
            $this->redirect('/course-club');
            return;
        }

        $data = $this->getPostData();
        $errors = $this->validateCourseClubData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect("/course-club/{$id}/edit");
            return;
        }

        // Check if hole number already exists for this club (excluding current record)
        if ($courseClubService->holeNumberExists($data['name_club'], (int) $data['number_hole'], $id, $data['gender'])) {
            $_SESSION['errors'] = ['number_hole' => 'Hole number already exists for this club'];
            $_SESSION['old'] = $data;
            $this->redirect("/course-club/{$id}/edit");
            return;
        }

        // Store edit as pending instead of saving directly
        if (!isset($_SESSION['pendingEdits'])) {
            $_SESSION['pendingEdits'] = [];
        }
        
        $_SESSION['pendingEdits'][$id] = [
            'id' => $id,
            'name_hole' => $data['name_hole'],
            'par' => (int) $data['par'],
            'stroke' => (int) $data['stroke']
        ];
        
        $_SESSION['success'] = 'Edit saved as pending. Return to Course Holes to apply all edits.';
        
        $this->redirect('/course-club#' . $courseClub->getNameClub() . '-' . $courseClub->getGender());
    }

    /**
     * Batch update course clubs with stroke uniqueness validation
     */
    public function batchUpdate(): void
    {
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getPostData();
            
            // Validate that we have course clubs data
            if (empty($data['course_clubs']) || !is_array($data['course_clubs'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No course club data provided'
                ]);
                return;
            }
            
            // Extract stroke values and validate uniqueness
            $strokes = [];
            $courseIds = [];
            $errors = [];
            
            foreach ($data['course_clubs'] as $clubData) {
                $courseId = $clubData['id'] ?? null;
                $stroke = $clubData['stroke'] ?? null;
                
                if ($courseId) {
                    $courseIds[] = $courseId;
                }
                
                if ($stroke !== null && $stroke !== '') {
                    $stroke = (int) $stroke;
                    
                    // Validate stroke is in range 1-18
                    if ($stroke < 1 || $stroke > 18) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => "Stroke index must be between 1 and 18. Found: $stroke"
                        ]);
                        return;
                    }
                    
                    // Check for duplicates
                    if (isset($strokes[$stroke])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => "Duplicate stroke index '$stroke' found. Each stroke index (1-18) can only be assigned to one hole."
                        ]);
                        return;
                    }
                    $strokes[$stroke] = true;
                }
            }
            
            // Validate that all stroke indices 1-18 are covered
            $missingStrokes = [];
            for ($i = 1; $i <= 18; $i++) {
                if (!isset($strokes[$i])) {
                    $missingStrokes[] = $i;
                }
            }
            
            if (!empty($missingStrokes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Missing stroke indices: " . implode(', ', $missingStrokes) . ". All 18 holes must have stroke indices 1-18, each used exactly once."
                ]);
                return;
            }
            
            // Validate that we have exactly 18 holes being updated
            if (count($data['course_clubs']) !== 18) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Batch must include all 18 holes for the selected club and gender. Found: " . count($data['course_clubs'])
                ]);
                return;
            }
            
            // Update each course club
            $courseClubService = $this->getCourseClubService();
            $successCount = 0;
            $totalCount = count($data['course_clubs']);
            
            foreach ($data['course_clubs'] as $clubData) {
                $courseId = $clubData['id'] ?? null;
                $nameHole = $clubData['name_hole'] ?? null;
                $par = $clubData['par'] ?? null;
                $stroke = $clubData['stroke'] ?? null;
                
                if ($courseId && $nameHole !== null && $par !== null && $stroke !== null) {
                    $courseClub = $courseClubService->getCourseClubById($courseId);
                    
                    if ($courseClub) {
                        // Validate individual course club data
                        $individualData = [
                            'name_hole' => $nameHole,
                            'par' => $par,
                            'stroke' => $stroke
                        ];
                        
                        $validationErrors = $this->validateCourseClubData(array_merge($individualData, [
                            'name_club' => $courseClub->getNameClub(),
                            'number_hole' => $courseClub->getNumberHole(),
                            'gender' => $courseClub->getGender()
                        ]));
                        
                        if (empty($validationErrors)) {
                            $courseClub->setNameHole($nameHole);
                            $courseClub->setPar((int) $par);
                            $courseClub->setStroke((int) $stroke);
                            
                            $user = $this->app->getDatabase()->getAuth()->getUser();
                            $courseClub->setUpdatedBy($user['username']);
                            
                            if ($courseClubService->updateCourseClub($courseClub)) {
                                $successCount++;
                            }
                        }
                    }
                }
            }
            
            if ($successCount === $totalCount) {
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully updated {$successCount} course holes"
                ]);
                // Clear pending edits after successful application
                unset($_SESSION['pendingEdits']);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => "Updated {$successCount} out of {$totalCount} course holes"
                ]);
            }
        }
    }

    /**
     * Delete course club hole
     */
    public function delete(int $id): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $courseClub = $courseClubService->getCourseClubById($id);
        if (!$courseClub) {
            $_SESSION['errors'] = ['not_found' => 'Course hole not found'];
            $this->redirect('/course-club');
            return;
        }

        $user = $this->app->getDatabase()->getAuth()->getUser();
        
        if ($courseClubService->deleteCourseClub($id, $user['username'])) {
            $_SESSION['success'] = 'Course hole deleted successfully';
        } else {
            $_SESSION['errors'] = ['delete' => 'Failed to delete course hole'];
        }
        
        $this->redirect('/course-club');
    }

    /**
     * Display add course form
     */
    public function addCourse(): void
    {
        $this->requireRole('admin');
        
        $courseClubService = $this->getCourseClubService();
        $clubNames = $courseClubService->getUniqueClubNames();
        
        $this->render('course-club/add-course', [
            'clubNames' => $clubNames,
            'user' => $this->app->getDatabase()->getAuth()->getUser()
        ]);
    }

    /**
     * Store new course
     */
    public function storeCourse(): void
    {
        $this->requireRole('admin');
        
        $data = $this->getPostData();
        $errors = $this->validateCourseData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/course-club/add-course');
            return;
        }

        $courseClubService = $this->getCourseClubService();
        $courseName = trim($data['course_name']);
        
        // Check if course already exists
        if ($courseClubService->courseExists($courseName)) {
            $_SESSION['errors'] = ['course_name' => 'Course/Club already exists'];
            $_SESSION['old'] = $data;
            $this->redirect('/course-club/add-course');
            return;
        }

        // Store the new course name in session for redirect
        $_SESSION['newCourseAdded'] = $courseName;
        $_SESSION['success'] = "Course '{$courseName}' created successfully. You can now add holes for this course.";
        
        // Redirect to bulk create form with the new course selected
        $this->redirect('/course-club/bulk-create?course=' . urlencode($courseName));
    }

    /**
     * Display bulk create form for 18 holes
     */
    public function bulkCreate(): void
    {
        $this->requireRole('admin');
        
        $courseName = urldecode($_GET['course'] ?? $_SESSION['newCourseAdded'] ?? '');
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        
        if (empty($courseName)) {
            $_SESSION['errors'] = ['course' => 'Course name is required'];
            $this->redirect('/course-club/add-course');
            return;
        }

        // Clear the one-time new course session variable
        if (isset($_SESSION['newCourseAdded'])) {
            unset($_SESSION['newCourseAdded']);
        }
        
        $this->render('course-club/bulk-create', [
            'courseName' => $courseName,
            'errors' => $errors,
            'old' => $old,
            'user' => $this->app->getDatabase()->getAuth()->getUser()
        ]);
    }

    /**
     * Store all 18 holes at once
     */
    public function bulkStore(): void
    {
        $this->requireRole('admin');
        
        // Use $_POST directly for form data (not JSON)
        $courseName = trim($_POST['club_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $holesData = $_POST['holes'] ?? [];
        
        // Validate header data
        if (empty($courseName) || empty($gender) || !in_array($gender, ['M', 'F'])) {
            $_SESSION['errors'] = ['header' => 'Invalid course name or gender'];
            $_SESSION['old'] = [
                'gender' => $gender,
                'holes' => $holesData
            ];
            $this->redirect('/course-club/bulk-create?course=' . urlencode($courseName));
            return;
        }

        // Validate we have exactly 18 holes
        if (empty($holesData) || !is_array($holesData) || count($holesData) !== 18) {
            $_SESSION['errors'] = ['holes' => 'All 18 holes must be provided. Found: ' . count($holesData)];
            $_SESSION['old'] = [
                'gender' => $gender,
                'holes' => $holesData
            ];
            $this->redirect('/course-club/bulk-create?course=' . urlencode($courseName));
            return;
        }

        // Validate and prepare holes
        $courseClubService = $this->getCourseClubService();
        $errors = [];
        $strokes = [];
        $holes = [];

        foreach ($holesData as $holeNumber => $holeData) {
            $holeNumber = (int) $holeNumber;
            $name = trim($holeData['name'] ?? '');
            $par = (int) ($holeData['par'] ?? 0);
            $stroke = (int) ($holeData['stroke'] ?? 0);

            // Validate each hole
            if (empty($name) || strlen($name) > 24) {
                $errors[] = "Hole {$holeNumber}: Name is required and must not exceed 24 characters";
            }
            if ($par < 3 || $par > 5) {
                $errors[] = "Hole {$holeNumber}: Par must be 3, 4, or 5";
            }
            if ($stroke < 1 || $stroke > 18) {
                $errors[] = "Hole {$holeNumber}: Stroke index must be between 1 and 18";
            }

            // Check for duplicate stroke indices
                // Only track valid strokes for duplicate checking
                if ($stroke >= 1 && $stroke <= 18) {
                    if (isset($strokes[$stroke])) {
                        $errors[] = "Hole {$holeNumber}: Stroke index {$stroke} is used more than once";
                    }
                    $strokes[$stroke] = $holeNumber;
                }

            // Check if hole already exists
            if ($courseClubService->holeNumberExists($courseName, $holeNumber, null, $gender)) {
                $errors[] = "Hole {$holeNumber} already exists for this course and gender";
            }

            $holes[$holeNumber] = [
                'number' => $holeNumber,
                'name' => $name,
                'par' => $par,
                'stroke' => $stroke
            ];
        }

        // Validate all stroke indices 1-18 are covered
        $missingStrokes = [];
        for ($i = 1; $i <= 18; $i++) {
            if (!isset($strokes[$i])) {
                $missingStrokes[] = $i;
            }
        }
        if (!empty($missingStrokes)) {
            $errors[] = "Missing stroke indices: " . implode(', ', $missingStrokes) . ". All must be 1-18, each used once.";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'gender' => $gender,
                'holes' => $holesData
            ];
            $this->redirect('/course-club/bulk-create?course=' . urlencode($courseName));
            return;
        }

        // Create all holes
        $user = $this->app->getDatabase()->getAuth()->getUser();
        $successCount = 0;
        
        foreach ($holes as $holeNumber => $holeData) {
            $courseClub = new \App\Models\CourseClub(
                $courseName,
                $holeNumber,
                $holeData['name'],
                $gender,
                $holeData['par'],
                $holeData['stroke'],
                $user['username']
            );

            if ($courseClubService->createCourseClub($courseClub)) {
                $successCount++;
            }
        }

        if ($successCount === 18) {
            $_SESSION['success'] = "Successfully created all 18 holes for {$courseName}";
            $this->redirect('/course-club');
        } else {
            $_SESSION['errors'] = ["Created {$successCount}/18 holes, but some failed"];
            $_SESSION['old'] = [
                'gender' => $gender,
                'holes' => $holesData
            ];
            $this->redirect('/course-club/bulk-create?course=' . urlencode($courseName));
        }
    }

    /**
     * Display course club statistics
     */
    /**
     * Validate course data
     */
    private function validateCourseData(array $data): array
    {
        $errors = [];
        
        if (empty($data['course_name'])) {
            $errors['course_name'] = 'Course name is required';
        } elseif (strlen($data['course_name']) > 16) {
            $errors['course_name'] = 'Course name must not exceed 16 characters';
        }
        
        return $errors;
    }

    /**
     * Validate course club data
     */
    private function validateCourseClubData(array $data): array
    {
        $errors = [];
        
        if (empty($data['name_club'])) {
            $errors['name_club'] = 'Club name is required';
        }
        
        if (empty($data['number_hole']) || !is_numeric($data['number_hole']) || $data['number_hole'] < 1 || $data['number_hole'] > 18) {
            $errors['number_hole'] = 'Hole number must be between 1 and 18';
        }
        
        if (empty($data['name_hole'])) {
            $errors['name_hole'] = 'Hole name is required';
        }
        
        if (empty($data['gender']) || !in_array($data['gender'], ['M', 'F'])) {
            $errors['gender'] = 'Gender must be M or F';
        }
        
        if (empty($data['par']) || !is_numeric($data['par']) || $data['par'] < 3 || $data['par'] > 5) {
            $errors['par'] = 'Par must be between 3 and 5';
        }
        
        if (empty($data['stroke']) || !is_numeric($data['stroke']) || $data['stroke'] < 1 || $data['stroke'] > 18) {
            $errors['stroke'] = 'Stroke index must be between 1 and 18';
        }
        
        return $errors;
    }
}
