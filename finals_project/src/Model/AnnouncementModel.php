<?php

namespace App\Model;

use App\Helpers\Database;

class AnnouncementModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new announcement
     */
    public function create(array $data): string|false
    {
        try {
            return $this->db->insert('announcements', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get announcements for classes a student is enrolled in
     */
    public function getForStudent(int $studentId): array
    {
        return $this->db->query(
            "SELECT a.*, c.subject, c.section, u.username AS teacher_name
             FROM announcements a
             JOIN classes c ON a.class_id = c.id
             JOIN users u ON a.posted_by = u.id
             JOIN enrollments e ON c.id = e.class_id
             WHERE e.student_id = :student_id
             ORDER BY a.created_at DESC",
            ['student_id' => $studentId]
        );
    }

    /**
     * Get announcements for a specific class
     */
    public function getByClass(int $classId): array
    {
        return $this->db->query(
            "SELECT a.*, u.username AS teacher_name
             FROM announcements a
             JOIN users u ON a.posted_by = u.id
             WHERE a.class_id = :class_id
             ORDER BY a.created_at DESC",
            ['class_id' => $classId]
        );
    }

    /**
     * Teacher: list announcements they posted
     */
    public function getForTeacher(int $teacherId): array
    {
        // Expect announcements table has: id, class_id, posted_by, title, content, image_url, publish_date, created_at
        return $this->db->query(
            "SELECT a.*, c.subject, c.section
             FROM announcements a
             LEFT JOIN classes c ON a.class_id = c.id
             WHERE a.posted_by = :teacher_id
             ORDER BY a.created_at DESC",
            ['teacher_id' => $teacherId]
        );
    }

    /**
     * Teacher: get one announcement ensuring it belongs to teacher
     */
    public function getOneForTeacher(int $announcementId, int $teacherId): ?array
    {
        return $this->db->queryOne(
            "SELECT a.*, c.subject, c.section
             FROM announcements a
             LEFT JOIN classes c ON a.class_id = c.id
             WHERE a.id = :announcement_id AND a.posted_by = :teacher_id",
            ['announcement_id' => $announcementId, 'teacher_id' => $teacherId]
        );
    }

    /**
     * Teacher: update announcement (only if belongs to teacher)
     */
    public function updateForTeacher(int $announcementId, int $teacherId, array $data): bool
    {
        // Ensure owner check via WHERE clause
        $rows = $this->db->update(
            'announcements',
            $data,
            'id = :id AND posted_by = :teacher_id',
            ['id' => $announcementId, 'teacher_id' => $teacherId]
        );

        return $rows > 0;
    }

    /**
     * Teacher: delete announcement (only if belongs to teacher)
     */
    public function deleteForTeacher(int $announcementId, int $teacherId): bool
    {
        $rows = $this->db->delete(
            'announcements',
            'id = :id AND posted_by = :teacher_id',
            ['id' => $announcementId, 'teacher_id' => $teacherId]
        );

        return $rows > 0;
    }
}

