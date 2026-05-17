<?php
namespace App\Model;

use App\Helpers\Database;

class UserModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user in the database
     */
    public function createUser(array $data): string|false
    {
        try {
            return $this->db->insert('users', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user by email address
     */
    public function getUserByEmail(string $email): array|null
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
    }

    /**
     * Get user by username
     */
    public function getUserByUsername(string $username): array|null
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE username = :username LIMIT 1",
            ['username' => $username]
        );
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): array|null
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Get all students (for teacher master list)
     */
    public function getAllStudents(): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.email, u.created_at,
                (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = u.id) AS enrolled_classes
            FROM users u WHERE u.role = 'student' ORDER BY u.username"
        );
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $id, array $data): int
    {
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]);
    }
}
