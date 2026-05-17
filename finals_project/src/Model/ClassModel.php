<?php
namespace App\Model;

use App\Helpers\Database;

class ClassModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new class
     */
    public function createClass(array $data): string|false
    {
        try {
            return $this->db->insert('classes', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get classes by teacher ID
     */
    public function getClassesByTeacher(int $teacherId): array
    {
        return $this->db->query(
            "SELECT c.*, 
                (SELECT COUNT(*) FROM enrollments WHERE class_id = c.id) as student_count 
             FROM classes c 
             WHERE c.teacher_id = :teacher_id 
             ORDER BY c.created_at DESC",
            ['teacher_id' => $teacherId]
        );
    }

    /**
     * Get class by invite code
     */
    public function getClassByCode(string $code): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM classes WHERE invite_code = :code LIMIT 1",
            ['code' => $code]
        );
    }

    /**
     * Get class by ID
     */
    public function getClassById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT c.*, u.username as teacher_name, u.email as teacher_email 
             FROM classes c 
             JOIN users u ON c.teacher_id = u.id 
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Get classes a student is enrolled in
     */
    public function getClassesByStudent(int $studentId): array
    {
        return $this->db->query(
            "SELECT c.*, u.username as teacher_name 
             FROM classes c 
             JOIN enrollments e ON c.id = e.class_id 
             JOIN users u ON c.teacher_id = u.id 
             WHERE e.student_id = :student_id 
             ORDER BY c.created_at DESC",
            ['student_id' => $studentId]
        );
    }

    /**
     * Check if invite code exists
     */
    public function codeExists(string $code): bool
    {
        $result = $this->db->queryOne(
            "SELECT 1 FROM classes WHERE invite_code = :code LIMIT 1",
            ['code' => $code]
        );
        return !empty($result);
    }
}

