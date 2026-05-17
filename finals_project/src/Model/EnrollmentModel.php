<?php
namespace App\Model;

use App\Helpers\Database;

class EnrollmentModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Enroll a student in a class
     */
    public function enroll(int $studentId, int $classId): string|false
    {
        try {
            return $this->db->insert('enrollments', [
                'student_id' => $studentId,
                'class_id' => $classId
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if student is already enrolled
     */
    public function isEnrolled(int $studentId, int $classId): bool
    {
        $result = $this->db->queryOne(
            "SELECT 1 FROM enrollments WHERE student_id = :student_id AND class_id = :class_id LIMIT 1",
            ['student_id' => $studentId, 'class_id' => $classId]
        );
        return !empty($result);
    }

    /**
     * Get all students in a class
     */
    public function getClassStudents(int $classId): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.email, u.created_at, e.joined_at 
             FROM users u 
             JOIN enrollments e ON u.id = e.student_id 
             WHERE e.class_id = :class_id 
             ORDER BY e.joined_at ASC",
            ['class_id' => $classId]
        );
    }

    /**
     * Get enrollment count for a class
     */
    public function getEnrollmentCount(int $classId): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM enrollments WHERE class_id = :class_id",
            ['class_id' => $classId]
        );
        return $result['count'] ?? 0;
    }

    /**
     * Get all enrollments across all classes (for master student list)
     */
    public function getAllEnrollments(): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.email, u.created_at, 
                    COUNT(e.class_id) as enrolled_classes 
             FROM users u 
             LEFT JOIN enrollments e ON u.id = e.student_id 
             WHERE u.role = 'student' 
             GROUP BY u.id 
             ORDER BY u.created_at DESC"
        );
    }

    /**
     * Get students in a specific class, excluding the querying student
     */
    public function getClassmates(int $classId, int $excludeStudentId): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.email, e.joined_at 
             FROM users u 
             JOIN enrollments e ON u.id = e.student_id 
             WHERE e.class_id = :class_id 
               AND e.student_id <> :exclude_id 
             ORDER BY u.username",
            ['class_id' => $classId, 'exclude_id' => $excludeStudentId]
        );
    }

    /**
     * Get teacher for a specific class
     */
    public function getTeacherForClass(int $classId): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.email 
             FROM users u 
             JOIN classes c ON u.id = c.teacher_id 
             WHERE c.id = :class_id 
             LIMIT 1",
            ['class_id' => $classId]
        );
    }
}
