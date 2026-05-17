<?php
namespace App\Helpers;

class Validator {
    private $errors = [];
    
    /**
     * Validate username
     */
    public function validateUsername($username) {
        if (empty($username)) {
            $this->errors[] = "Username is required";
            return false;
        }
        
        if (strlen($username) < 3) {
            $this->errors[] = "Username must be at least 3 characters long";
            return false;
        }
        
        if (strlen($username) > 50) {
            $this->errors[] = "Username cannot exceed 50 characters";
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', $username)) {
            $this->errors[] = "Username must start with a letter and can only contain letters, numbers, underscores and dots";
            return false;
        }
        
        // Check for SQL injection patterns
        $sqlPatterns = ['/\bSELECT\b/i', '/\bINSERT\b/i', '/\bUPDATE\b/i', '/\bDELETE\b/i', '/\bDROP\b/i', '/--/', '/;\s*$/'];
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $username)) {
                $this->errors[] = "Username contains invalid characters or patterns";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        if (empty($email)) {
            $this->errors[] = "Email is required";
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format";
            return false;
        }
        
        if (strlen($email) > 100) {
            $this->errors[] = "Email cannot exceed 100 characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password
     */
    public function validatePassword($password, $confirmPassword = null) {
        if (empty($password)) {
            $this->errors[] = "Password is required";
            return false;
        }
        
        if (strlen($password) < 8) {
            $this->errors[] = "Password must be at least 8 characters long";
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors[] = "Password must contain at least one uppercase letter";
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $this->errors[] = "Password must contain at least one lowercase letter";
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $this->errors[] = "Password must contain at least one number";
            return false;
        }
        
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $this->errors[] = "Password must contain at least one special character";
            return false;
        }
        
        if ($confirmPassword !== null && $password !== $confirmPassword) {
            $this->errors[] = "Passwords do not match";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    public function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->errors[] = "Required fields missing: " . implode(', ', $missing);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
?>