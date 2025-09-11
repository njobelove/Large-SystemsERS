<?php
require_once 'config.php';

class Auth {
    private $pdo;
    
    public function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->pdo = getDBConnection();
    }
    
    // User login
    public function login($username, $password, $role) {
        try {
            // First check if users table exists and has required columns
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'users'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Users table does not exist");
                return false;
            }

            // Check table structure
            $stmt = $this->pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['username', 'password', 'role', 'registration_status'];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $columns)) {
                    error_log("Required column '$col' missing from users table");
                    return false;
                }
            }

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Check if user is approved (case-insensitive)
                $status = strtolower($user['registration_status']);
                if ($status !== 'approved') {
                    error_log("User not approved: " . $username . " (status: " . $user['registration_status'] . ")");
                    return false; // User not approved
                }

                // Check password - with fallback for different hash types
                $passwordValid = false;
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } elseif (md5($password) === $user['password']) {
                    // Fallback for MD5 hashed passwords (update to proper hash)
                    error_log("MD5 password detected for user: " . $username . " - updating to secure hash");
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $updateStmt->bindParam(':password', $newHash);
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                    $passwordValid = true;
                }

                if ($passwordValid) {
                    // Clear any existing session data
                    session_unset();

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['login_time'] = time();

                    error_log("Login successful for: " . $username);
                    return true;
                } else {
                    error_log("Password verification failed for: " . $username);
                }
            } else {
                error_log("User not found: " . $username . " with role: " . $role);
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get current user role
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }

    // Alias for getRole() to maintain compatibility with login.php
    public function getUserRole() {
        return $this->getRole();
    }
    
    // Get current user ID
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user data
    public function getUserData() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name']
            ];
        }
        return null;
    }
    
    // User logout
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    // Check if user has specific role
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    // Check if user is approved (additional method for checking approval status)
    public function isApproved($user_id = null) {
        try {
            $user_id = $user_id ?? $this->getUserId();
            
            if (!$user_id) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("SELECT registration_status FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($user && $user['registration_status'] === 'approved');
        } catch(PDOException $e) {
            error_log("Approval check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Helper method to create a hashed password (use this when creating users)
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
?>