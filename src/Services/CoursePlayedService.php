<?php

namespace App\Services;

use App\Core\Database;

class CoursePlayedService
{
    private Database $db;
    private Logger $logger;

    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getAllCoursesPlayed(): array
    {
        $sql = "
            SELECT cp.row_id, cp.name_course, cp.name_club, cp.ident_eclectic, cp.updated_by, cp.updated_ts,
                   COUNT(cph.row_id) AS mapped_holes
            FROM course_played cp
            LEFT JOIN course_played_hole cph ON cph.course_played_id = cp.row_id
            GROUP BY cp.row_id, cp.name_course, cp.name_club, cp.ident_eclectic, cp.updated_by, cp.updated_ts
            ORDER BY cp.name_club, cp.name_course
        ";

        return $this->db->fetchAll($sql);
    }

    public function getCoursePlayedById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM course_played WHERE row_id = ?", [$id]);
    }

    public function getCoursePlayedHoles(int $coursePlayedId): array
    {
        return $this->db->fetchAll(
            "SELECT number_hole FROM course_played_hole WHERE course_played_id = ? ORDER BY number_hole",
            [$coursePlayedId]
        );
    }

    public function getUniqueClubNames(): array
    {
        $rows = $this->db->fetchAll("SELECT DISTINCT name_club FROM course_club ORDER BY name_club");
        return array_column($rows, 'name_club');
    }

    public function courseExistsForClub(string $nameCourse, string $nameClub, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM course_played WHERE name_course = ? AND name_club = ?";
        $params = [$nameCourse, $nameClub];

        if ($excludeId !== null) {
            $sql .= " AND row_id <> ?";
            $params[] = $excludeId;
        }

        $row = $this->db->fetchOne($sql, $params);
        return ((int) ($row['count'] ?? 0)) > 0;
    }

    public function createCoursePlayed(array $data, array $numberHoles, string $username): bool
    {
        $this->db->beginTransaction();

        try {
            $coursePlayedId = $this->db->insert('course_played', [
                'name_course' => $data['name_course'],
                'name_club' => $data['name_club'],
                'ident_eclectic' => $data['ident_eclectic'],
                'updated_by' => $username,
            ]);

            foreach ($numberHoles as $numberHole) {
                $this->db->insert('course_played_hole', [
                    'course_played_id' => $coursePlayedId,
                    'number_hole' => $numberHole,
                    'updated_by' => $username,
                ]);
            }

            $this->db->commit();
            $this->logger->logConfig('course_played_created', [
                'row_id' => $coursePlayedId,
                'name_course' => $data['name_course'],
                'name_club' => $data['name_club'],
                'ident_eclectic' => $data['ident_eclectic'],
            ], $username);

            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error('Create course_played failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateCoursePlayed(int $id, array $data, array $numberHoles, string $username): bool
    {
        $this->db->beginTransaction();

        try {
            $this->db->update('course_played', [
                'name_course' => $data['name_course'],
                'name_club' => $data['name_club'],
                'ident_eclectic' => $data['ident_eclectic'],
                'updated_by' => $username,
            ], ['row_id' => $id]);

            $this->db->delete('course_played_hole', ['course_played_id' => $id]);

            foreach ($numberHoles as $numberHole) {
                $this->db->insert('course_played_hole', [
                    'course_played_id' => $id,
                    'number_hole' => $numberHole,
                    'updated_by' => $username,
                ]);
            }

            $this->db->commit();
            $this->logger->logConfig('course_played_updated', [
                'row_id' => $id,
                'name_course' => $data['name_course'],
                'name_club' => $data['name_club'],
                'ident_eclectic' => $data['ident_eclectic'],
            ], $username);

            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error('Update course_played failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteCoursePlayed(int $id, string $username): bool
    {
        $course = $this->getCoursePlayedById($id);
        if (!$course) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $this->db->delete('course_played_hole', ['course_played_id' => $id]);
            $this->db->delete('course_played', ['row_id' => $id]);
            $this->db->commit();

            $this->logger->logConfig('course_played_deleted', [
                'row_id' => $id,
                'name_course' => $course['name_course'],
                'name_club' => $course['name_club'],
            ], $username);

            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error('Delete course_played failed: ' . $e->getMessage());
            return false;
        }
    }
}
