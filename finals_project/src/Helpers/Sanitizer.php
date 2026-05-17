<?php
namespace App\Helpers;

class Sanitizer {
    
    public function sanitizeUsername($username) {
        if ($username === null) return '';
        
        $sanitized = trim($username); 
        
        $sanitized = strip_tags($sanitized);
        
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);
        
        return $sanitized;
    }
    
    public function sanitizeEmail($email) {
        if ($email === null) return '';
        
        $sanitized = trim($email);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = filter_var($sanitized, FILTER_SANITIZE_EMAIL);
        
        return $sanitized;
    }
    
    public function sanitizePassword($password) {
        if ($password === null) return '';
        
        return trim($password);
    }

    public function sanitizeString($input) {
        if ($input === null) return '';
        
        $sanitized = trim($input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
    
    public function sanitizeArray($data, $fieldTypes = []) {
        $sanitized = [];
        
        foreach ($data as $field => $value) {
            if (isset($fieldTypes[$field])) {
                switch ($fieldTypes[$field]) {
                    case 'username':
                        $sanitized[$field] = $this->sanitizeUsername($value);
                        break;
                    case 'email':
                        $sanitized[$field] = $this->sanitizeEmail($value);
                        break;
                    case 'password':
                        $sanitized[$field] = $this->sanitizePassword($value);
                        break;
                    default:
                        $sanitized[$field] = $this->sanitizeString($value);
                }
            } else {
                $sanitized[$field] = $this->sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
}
?>