<?php

namespace App\Services;

use App\Core\Database;
use App\Models\CourseClub;
use App\Services\Logger;

class CourseClubService
{
    private Database $db;
    private Logger $logger;

    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get all course club holes
     */
    public function getAllCourseClubs(): array
    {
        $sql = "SELECT * FROM course_club ORDER BY name_club, number_hole";
        $results = $this->db->query($sql);
        
        $courseClubs = [];
        foreach ($results as $row) {
            $courseClubs[] = CourseClub::fromArray($row);
        }
        
        return $courseClubs;
    }

    /**
     * Get course club by ID
     */
    public function getCourseClubById(int $id): ?CourseClub
    {
        $sql = "SELECT * FROM course_club WHERE row_id = ?";
        $result = $this->db->query($sql, [$id]);
        
        $row = $result->fetch();
        if (!$row) {
            return null;
        }
        
        return CourseClub::fromArray($row);
    }

    /**
     * Get course clubs by club name
     */
    public function getCourseClubsByClub(string $nameClub): array
    {
        $sql = "SELECT * FROM course_club WHERE name_club = ? ORDER BY number_hole";
        $results = $this->db->query($sql, [$nameClub]);
        
        $courseClubs = [];
        foreach ($results as $row) {
            $courseClubs[] = CourseClub::fromArray($row);
        }
        
        return $courseClubs;
    }

    /**
     * Create a new course club hole
     */
    public function createCourseClub(CourseClub $courseClub): bool
    {
        $sql = "INSERT INTO course_club (name_club, number_hole, name_hole, gender, par, stroke, updated_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $courseClub->getNameClub(),
            $courseClub->getNumberHole(),
            $courseClub->getNameHole(),
            $courseClub->getGender(),
            $courseClub->getPar(),
            $courseClub->getStroke(),
            $courseClub->getUpdatedBy()
        ];
        
        try {
            $this->db->query($sql, $params);
            $this->logger->logCourseClubCreate(
                $courseClub->getNameClub(),
                $courseClub->getNumberHole(),
                $courseClub->getUpdatedBy()
            );
            return true;
        } catch (\Exception $e) {
            $this->logger->error('CourseClub creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing course club hole
     */
    public function updateCourseClub(CourseClub $courseClub): bool
    {
        $sql = "UPDATE course_club 
                SET name_club = ?, number_hole = ?, name_hole = ?, gender = ?, par = ?, stroke = ?, updated_by = ?
                WHERE row_id = ?";
        
        $params = [
            $courseClub->getNameClub(),
            $courseClub->getNumberHole(),
            $courseClub->getNameHole(),
            $courseClub->getGender(),
            $courseClub->getPar(),
            $courseClub->getStroke(),
            $courseClub->getUpdatedBy(),
            $courseClub->getRowId()
        ];
        
        try {
            // Debug: Store debugging info in session
            $_SESSION['service_debug'] = [
                'step' => 'Executing database update',
                'rowId' => $courseClub->getRowId(),
                'sql' => $sql,
                'params' => $params
            ];
            
            $result = $this->db->query($sql, $params);
            $rowCount = $result->rowCount();
            
            $_SESSION['service_debug']['rowCount'] = $rowCount;
            
            if ($rowCount > 0) {
                $this->logger->logCourseClubUpdate(
                    $courseClub->getNameClub(),
                    $courseClub->getNumberHole(),
                    $courseClub->getUpdatedBy()
                );
                $_SESSION['service_debug']['result'] = 'SUCCESS';
                return true;
            } else {
                $_SESSION['service_debug']['result'] = 'NO_ROWS_AFFECTED';
                $this->logger->error('CourseClub update: No rows affected. SQL: ' . $sql);
                $this->logger->error('Params: ' . json_encode($params));
                $this->logger->error('Row ID: ' . $courseClub->getRowId());
                return false;
            }
        } catch (\Exception $e) {
            $_SESSION['service_debug'] = [
                'step' => 'EXCEPTION_CAUGHT',
                'error' => $e->getMessage(),
                'sql' => $sql,
                'params' => $params,
                'result' => 'FAILED'
            ];
            $this->logger->error('CourseClub update failed: ' . $e->getMessage());
            $this->logger->error('SQL: ' . $sql);
            $this->logger->error('Params: ' . json_encode($params));
            return false;
        }
    }

    /**
     * Delete a course club hole
     */
    public function deleteCourseClub(int $id, string $updatedBy): bool
    {
        $courseClub = $this->getCourseClubById($id);
        if (!$courseClub) {
            return false;
        }
        
        $sql = "DELETE FROM course_club WHERE row_id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            $this->logger->logCourseClubDelete(
                $courseClub->getNameClub(),
                $courseClub->getNumberHole(),
                $updatedBy
            );
            return true;
        } catch (\Exception $e) {
            $this->logger->error('CourseClub deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if hole number already exists for a club
     */
    public function holeNumberExists(string $nameClub, int $numberHole, ?int $excludeId = null, ?string $gender = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM course_club WHERE name_club = ? AND gender = ? AND number_hole = ?";
        $params = [$nameClub, $gender ?? '', $numberHole];

        if ($excludeId) {
            $sql .= " AND row_id <> ?";
            $params[] = $excludeId;
        }

        
        $result = $this->db->query($sql, $params);
        $row = $result->fetch();
        return (int) $row["count"] > 0;
    }

    /**
     * Get unique club names
     */
    public function getUniqueClubNames(): array
    {
        $sql = "SELECT DISTINCT name_club FROM course_club ORDER BY name_club";
        $results = $this->db->query($sql);
        
        return array_column($results->fetchAll(), 'name_club');
    }

}
