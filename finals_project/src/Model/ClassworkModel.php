<?php

namespace App\Model;

use App\Helpers\Database;

class ClassworkModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new classwork
     */
    public function create(array $data): string|false
    {
        try {
            return $this->db->insert('classwork', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check that a classwork belongs to a given class.
     */
    public function existsInClass(int $classworkId, int $classId): bool
    {
        $row = $this->db->queryOne(
            "SELECT id FROM classwork WHERE id = :id AND class_id = :class_id LIMIT 1",
            ['id' => $classworkId, 'class_id' => $classId]
        );

        return !empty($row);
    }

    /**
     * Since the current DB schema does NOT include dedicated activity-attachment files,
     * we can’t persist “activity files” into classwork_files.
     *
     * Existing architecture supports:
     * - class_resources (class-level)
     * - submission_files (student submission attachments)
     *
     * This method intentionally returns false until the DB schema is extended.
     */
    public function addClassworkFileAttachment(array $data): string|false
    {
        return false;
    }


    /**

     * Get classwork for student's enrolled classes
     */
    public function getForStudent(int $studentId, ?string $subject = null, ?string $type = null): array
    {
        $sql = "SELECT cw.*, c.subject, c.section, u.username AS teacher_name
                FROM classwork cw
                JOIN classes c ON cw.class_id = c.id
                JOIN users u ON c.teacher_id = u.id
                JOIN enrollments e ON c.id = e.class_id
                WHERE e.student_id = :student_id";

        $params = ['student_id' => $studentId];

        if ($subject && $subject !== 'all') {
            $sql .= " AND c.subject = :subject";
            $params['subject'] = $subject;
        }

        if ($type && $type !== 'all') {
            $sql .= " AND cw.type = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY cw.due_date ASC, cw.created_at DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get classwork for a specific class
     */
    public function getByClass(int $classId): array
    {
        return $this->db->query(
            "SELECT cw.*, u.username AS teacher_name
             FROM classwork cw
             JOIN classes c ON cw.class_id = c.id
             JOIN users u ON c.teacher_id = u.id
             WHERE cw.class_id = :class_id
             ORDER BY cw.due_date ASC",
            ['class_id' => $classId]
        );
    }

    /**
     * Get pending classwork
     */
    public function getPending(int $studentId): array
    {
        return $this->db->query(
            "SELECT cw.*, c.subject, c.section
             FROM classwork cw
             JOIN classes c ON cw.class_id = c.id
             JOIN enrollments e ON c.id = e.class_id
             WHERE e.student_id = :student_id
             AND (cw.due_date IS NULL OR cw.due_date >= CURDATE())
             ORDER BY cw.due_date ASC
             LIMIT 5",
            ['student_id' => $studentId]
        );
    }

    /**
     * Get distinct subjects
     */
    public function getSubjectsForStudent(int $studentId): array
    {
        return $this->db->query(
            "SELECT DISTINCT c.subject
             FROM classes c
             JOIN enrollments e ON c.id = e.class_id
             WHERE e.student_id = :student_id
             ORDER BY c.subject",
            ['student_id' => $studentId]
        );
    }
}