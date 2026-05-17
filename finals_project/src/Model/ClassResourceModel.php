<?php
namespace App\Model;

use App\Helpers\Database;

class ClassResourceModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getResourcesForClass(int $classId): array
    {
        return $this->db->query(
            "SELECT r.id, r.class_id, r.filename, r.original_name, r.mime_type, r.file_size, r.created_at,
                    u.username as uploaded_by
             FROM class_resources r
             JOIN users u ON r.uploaded_by = u.id
             WHERE r.class_id = :class_id
             ORDER BY r.created_at DESC",
            ['class_id' => $classId]
        );
    }

    public function create(array $data): string|false
    {
        try {
            return $this->db->insert('class_resources', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteIfTeacherOwns(int $classId, int $resourceId, int $teacherId): bool
    {
        // Ensure resource belongs to a class owned by teacher.
        $result = $this->db->queryOne(
            "SELECT r.id
             FROM class_resources r
             JOIN classes c ON c.id = r.class_id
             WHERE r.id = :resource_id AND r.class_id = :class_id AND c.teacher_id = :teacher_id LIMIT 1",
            ['resource_id' => $resourceId, 'class_id' => $classId, 'teacher_id' => $teacherId]
        );

        if (empty($result)) return false;

        $this->db->delete('class_resources', 'id = :id', ['id' => $resourceId]);
        return true;
    }
}

