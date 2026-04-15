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
        
        $this->render('course-club/create', [
            'clubNames' => $clubNames,
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
     * Display course club statistics
     */
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
