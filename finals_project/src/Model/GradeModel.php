<?php
namespace App\Model;

use App\Helpers\Database;

class GradeModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function upsertGrade(int $submissionId, int $classworkId, int $classId, int $teacherId, ?int $score, ?string $feedback): string|false
    {
        $existing = $this->db->queryOne(
            "SELECT id FROM classwork_grades WHERE submission_id = :submission_id LIMIT 1",
            ['submission_id' => $submissionId]
        );

        if (!empty($existing['id'])) {
            $this->db->update(
                'classwork_grades',
                ['score' => $score, 'feedback' => $feedback, 'graded_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => (int)$existing['id']]
            );
            return (string)$existing['id'];
        }

        return $this->db->insert('classwork_grades', [
            'submission_id' => $submissionId,
            'classwork_id' => $classworkId,
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'score' => $score,
            'feedback' => $feedback
        ]);
    }

    public function getGradeForSubmission(int $submissionId): ?array
    {
        return $this->db->queryOne(
            "SELECT g.*, u.username AS teacher_username
             FROM classwork_grades g
             JOIN users u ON u.id = g.teacher_id
             WHERE g.submission_id = :submission_id
             LIMIT 1",
            ['submission_id' => $submissionId]
        );
    }

    // List all submissions for a classwork item including grade
    public function getSubmissionsWithGradesForClasswork(int $classworkId, int $classId): array
    {
        return $this->db->query(
            "SELECT s.*, u.username AS student_username,
                    g.score, g.feedback, g.graded_at, gt.username AS teacher_username
             FROM classwork_submissions s
             JOIN users u ON u.id = s.student_id
             LEFT JOIN classwork_grades g ON g.submission_id = s.id
             LEFT JOIN users gt ON gt.id = g.teacher_id
             WHERE s.classwork_id = :classwork_id AND s.class_id = :class_id
             ORDER BY s.submitted_at DESC",
            ['classwork_id' => $classworkId, 'class_id' => $classId]
        );
    }
}


 