<?php
namespace App\Model;

use App\Helpers\Database;

class CalendarModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a calendar event
     */
    public function createEvent(array $data): string|false
    {
        try {
            return $this->db->insert('calendar_events', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get calendar events for a specific month/year for classes a student is enrolled in
     */
    public function getByStudent(int $studentId, int $month, int $year): array
    {
        return $this->db->query(
            "SELECT ce.*, c.subject as class_name
             FROM calendar_events ce
             JOIN classes c ON ce.class_id = c.id
             JOIN enrollments e ON c.id = e.class_id
             WHERE e.student_id = :student_id
               AND MONTH(ce.event_date) = :month
               AND YEAR(ce.event_date) = :year
             ORDER BY ce.event_date ASC",
            ['student_id' => $studentId, 'month' => $month, 'year' => $year]
        );
    }

    /**
     * Get calendar events for classes taught by a teacher
     */
    public function getByTeacher(int $teacherId, int $month, int $year): array
    {
        return $this->db->query(
            "SELECT ce.*, c.subject as class_name
             FROM calendar_events ce
             JOIN classes c ON ce.class_id = c.id
             WHERE c.teacher_id = :teacher_id
               AND MONTH(ce.event_date) = :month
               AND YEAR(ce.event_date) = :year
             ORDER BY ce.event_date ASC",
            ['teacher_id' => $teacherId, 'month' => $month, 'year' => $year]
        );
    }

    /**
     * Get events for a specific class
     */
    public function getByClass(int $classId): array
    {
        return $this->db->query(
            "SELECT * FROM calendar_events 
             WHERE class_id = :class_id 
             ORDER BY event_date ASC",
            ['class_id' => $classId]
        );
    }

    /**
     * Delete an event
     */
    public function deleteEvent(int $eventId): int
    {
        return $this->db->delete('calendar_events', 'id = :id', ['id' => $eventId]);
    }
}
