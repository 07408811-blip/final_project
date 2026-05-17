<?php
namespace App\Model;

use App\Helpers\Database;

class SubmissionModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Create a submission (text + implicit/optional files)
    public function upsertTextSubmission(int $classworkId, int $classId, int $studentId, ?string $submittedText): string|false
    {
        // Unique: (classwork_id, student_id) so we can upsert using a two-step approach.
        $existing = $this->db->queryOne(
            "SELECT id FROM classwork_submissions WHERE classwork_id = :classwork_id AND student_id = :student_id LIMIT 1",
            ['classwork_id' => $classworkId, 'student_id' => $studentId]
        );

        if (!empty($existing['id'])) {
            return $this->db->update(
                'classwork_submissions',
                ['submitted_text' => $submittedText],
                'id = :id',
                ['id' => (int)$existing['id']]
            ) ? (string)$existing['id'] : false;
        }

        return $this->db->insert('classwork_submissions', [
            'classwork_id' => $classworkId,
            'class_id' => $classId,
            'student_id' => $studentId,
            'submitted_text' => $submittedText
        ]);
    }

    public function getSubmissionForStudent(int $classworkId, int $classId, int $studentId): ?array
    {
        return $this->db->queryOne(
            "SELECT s.*, cw.title AS classwork_title
             FROM classwork_submissions s
             JOIN classwork cw ON cw.id = s.classwork_id
             WHERE s.classwork_id = :classwork_id AND s.class_id = :class_id AND s.student_id = :student_id
             LIMIT 1",
            ['classwork_id' => $classworkId, 'class_id' => $classId, 'student_id' => $studentId]
        );
    }

    // List submissions by classwork for teacher
    public function getSubmissionsForClasswork(int $classworkId, int $classId): array
    {
        return $this->db->query(
            "SELECT s.*, u.username AS student_username
             FROM classwork_submissions s
             JOIN users u ON u.id = s.student_id
             WHERE s.classwork_id = :classwork_id AND s.class_id = :class_id
             ORDER BY s.submitted_at DESC",
            ['classwork_id' => $classworkId, 'class_id' => $classId]
        );
    }

    // List student's submissions across classwork items inside a class
    public function getSubmissionsForStudentInClass(int $classId, int $studentId): array
    {
        return $this->db->query(
            "SELECT s.*, cw.id AS classwork_id, cw.title AS classwork_title, cw.type AS classwork_type,
                    cw.due_date, g.score, g.feedback
             FROM classwork_submissions s
             JOIN classwork cw ON cw.id = s.classwork_id
             LEFT JOIN classwork_grades g ON g.submission_id = s.id
             WHERE s.class_id = :class_id AND s.student_id = :student_id
             ORDER BY cw.due_date ASC, s.submitted_at DESC",
            ['class_id' => $classId, 'student_id' => $studentId]
        );
    }

    public function listSubmissionFiles(int $submissionId): array
    {
        return $this->db->query(
            "SELECT * FROM submission_files WHERE submission_id = :submission_id ORDER BY created_at DESC",
            ['submission_id' => $submissionId]
        );
    }

    public function addSubmissionFile(int $submissionId, array $fileRow): string|false
    {
        return $this->db->insert('submission_files', [
            'submission_id' => $submissionId,
            'filename' => $fileRow['stored_filename'],
            'original_name' => $fileRow['original_name'],
            'mime_type' => $fileRow['mime_type'] ?? null,
            'file_size' => $fileRow['file_size'] ?? 0
        ]);
    }

    public function deleteSubmissionFileIfTeacherOwns(int $classworkId, int $classId, int $submissionFileId, int $teacherId): bool
    {
        // Ensure: the submission file belongs to a submission for that classwork, and that class is owned by the teacher.
        $result = $this->db->queryOne(
            "SELECT sf.id
             FROM submission_files sf
             JOIN classwork_submissions s ON s.id = sf.submission_id
             JOIN classwork cw ON cw.id = s.classwork_id
             JOIN classes c ON c.id = cw.class_id
             WHERE sf.id = :file_id AND cw.id = :classwork_id AND c.id = :class_id AND c.teacher_id = :teacher_id
             LIMIT 1",
            ['file_id' => $submissionFileId, 'classwork_id' => $classworkId, 'class_id' => $classId, 'teacher_id' => $teacherId]
        );

        if (empty($result)) return false;

        return $this->db->delete('submission_files', 'id = :id', ['id' => $submissionFileId]) ? true : false;
    }
}


