<?php
namespace App\Controller;

use App\Helpers\Sanitizer;
use App\Helpers\Validator;
use App\Model\UserModel;

class UserControl
{
    private UserModel $userModel;
    private Sanitizer $sanitizer;
    private Validator $validator;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->sanitizer = new Sanitizer();
        $this->validator = new Validator();
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $this->validator->clearErrors();

        // Define field types for sanitization
        $fieldTypes = [
            'username' => 'username',
            'email' => 'email',
            'password' => 'password',
            'confirm_password' => 'password',
            'role' => 'string'
        ];

        $clean = $this->sanitizer->sanitizeArray($data, $fieldTypes);

        // Validate required fields
        if (!$this->validator->validateRequired($clean, ['username', 'email', 'password', 'confirm_password', 'role'])) {
            return [
                'status' => false,
                'message' => 'Please fill in all required fields.',
                'errors' => $this->validator->getErrors()
            ];
        }

        // Validate individual fields
        if (!$this->validator->validateUsername($clean['username'])) {
            return [
                'status' => false,
                'message' => 'Invalid username.',
                'errors' => $this->validator->getErrors()
            ];
        }

        if (!$this->validator->validateEmail($clean['email'])) {
            return [
                'status' => false,
                'message' => 'Invalid email address.',
                'errors' => $this->validator->getErrors()
            ];
        }

        if (!$this->validator->validatePassword($clean['password'], $clean['confirm_password'])) {
            return [
                'status' => false,
                'message' => 'Password validation failed.',
                'errors' => $this->validator->getErrors()
            ];
        }

        // Check for existing username
        if ($this->userModel->getUserByUsername($clean['username'])) {
            return [
                'status' => false,
                'message' => 'Username already exists.',
                'errors' => ['Username already taken.']
            ];
        }

        // Check for existing email
        if ($this->userModel->getUserByEmail($clean['email'])) {
            return [
                'status' => false,
                'message' => 'Email already exists.',
                'errors' => ['Email already registered.']
            ];
        }

        // Prepare data for insertion
        $insertData = [
            'username' => $clean['username'],
            'email' => $clean['email'],
            'password' => password_hash($clean['password'], PASSWORD_DEFAULT),
            'role' => in_array($clean['role'], ['student', 'teacher']) ? $clean['role'] : 'student'
        ];

        $result = $this->userModel->createUser($insertData);

        if ($result === false) {
            return [
                'status' => false,
                'message' => 'Failed to create account. Please try again.',
                'errors' => ['Database error during registration.']
            ];
        }

        return [
            'status' => true,
            'message' => 'Account created successfully! You can now log in.',
            'errors' => []
        ];
    }

    /**
     * Log in an existing user
     */
    public function login(array $data): array
    {
        $this->validator->clearErrors();

        $fieldTypes = [
            'username' => 'username',
            'password' => 'password'
        ];

        $clean = $this->sanitizer->sanitizeArray($data, $fieldTypes);

        if (!$this->validator->validateRequired($clean, ['username', 'password'])) {
            return [
                'status' => false,
                'message' => 'Please enter both username and password.',
                'errors' => $this->validator->getErrors()
            ];
        }

        $user = $this->userModel->getUserByUsername($clean['username']);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'Invalid username or password.',
                'errors' => ['User not found.']
            ];
        }

        if (!password_verify($clean['password'], $user['password'])) {
            return [
                'status' => false,
                'message' => 'Invalid username or password.',
                'errors' => ['Incorrect password.']
            ];
        }

        // Remove password from returned data before storing in session
        unset($user['password']);

        return [
            'status' => true,
            'message' => 'Login successful.',
            'user' => $user,
            'errors' => []
        ];
    }
}
?>
