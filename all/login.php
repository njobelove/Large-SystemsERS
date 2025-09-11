<?php
session_start();
require_once 'auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if direct admin login button was clicked
    if (isset($_POST['direct_admin_login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = 'admin'; // Force role to admin for direct admin login
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
    }

    // Basic validation
    if (empty($username) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields.';
    } else {
        // Try to login with the provided credentials
        $login_result = $auth->login($username, $password, $role);

        if ($login_result) {
            // Get the current user's role after successful login
            $user_role = $auth->getUserRole();

            // Redirect to appropriate dashboard based on role
            if ($user_role === 'admin') {
                header('Location: SupperAdmin.php');
            } elseif ($user_role === 'staff') {
                header('Location: staff_dashboard.php');
            } elseif ($user_role === 'student') {
                header('Location: student_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            // More specific error checking with enhanced debugging
            $pdo = getDBConnection();
            try {
                $stmt = $pdo->prepare("SELECT id, registration_status, password FROM users WHERE username = :username AND role = :role");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':role', $role);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $status = strtolower($user['registration_status']);
                    if ($status !== 'approved') {
                        $error = 'Your account is not yet approved. Please contact an administrator. (Status: ' . ucfirst($user['registration_status']) . ')';
                        error_log("Login failed - User not approved: $username (Status: {$user['registration_status']})");
                    } else {
                        // Check password hash format for debugging
                        $password_hash = $user['password'];
                        if (password_verify($password, $password_hash)) {
                            $error = 'Login successful but redirect failed. Please contact administrator.';
                            error_log("Login verification passed but login method returned false for: $username");
                        } elseif (md5($password) === $password_hash) {
                            $error = 'Password hash needs updating. Please try again.';
                            error_log("MD5 password detected for user: $username - should be updated on next login");
                        } else {
                            $error = 'Invalid username or password. Please try again.';
                            error_log("Password verification failed for approved user: $username");
                        }
                    }
                } else {
                    $error = 'User not found with the specified role. Please check your credentials.';
                    error_log("User not found: $username with role: $role");
                }
            } catch(PDOException $e) {
                error_log("Login check error: " . $e->getMessage());
                $error = 'Login system temporarily unavailable. Please try again later.';
            }
        }
    }
}

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    $user_role = $auth->getUserRole();
    
    if ($user_role === 'admin') {
        header('Location: SupperAdmin.php');
    } else if ($user_role === 'staff') {
        header('Location: staff_dashboard.php');
    } else if ($user_role === 'student') {
        header('Location: student_dashboard.php');
    } else {
        header('Location: SupperAdmin.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP System - Login</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 1000px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-header h1 {
            color: #4361ee;
            margin-bottom: 5px;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        .login-form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .input-with-icon {
            position: relative;
        }
        .input-with-icon i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        .input-with-icon input, 
        .input-with-icon select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #4361ee;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #3a56d4;
        }
        .login-footer {
            text-align: center;
        }
        .login-footer a {
            color: #4361ee;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .system-info {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .system-info h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .modules {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .module {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 15px;
            border-radius: 5px;
        }
        .academic { background: #e8f5e9; }
        .finance { background: #e3f2fd; }
        .hr { background: #f3e5f5; }
        .module i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4361ee;
        }
        .module h4 {
            margin: 10px 0;
            color: #333;
        }
        .module p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>ERP System</h1>
            <p>Integrated Academic, Finance & HR Management</p>
        </div>
        
        <!-- Display error message if login fails -->
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="login-form">
            <h2>Login to Your Account</h2>
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">Login as</label>
                <div class="input-with-icon">
                    <i class="fas fa-user-tag"></i>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="login-btn">Login to Dashboard</button>

            <!-- Direct Admin Login Button -->
            <button type="submit" name="direct_admin_login" value="1" class="login-btn" style="background-color: #d32f2f; margin-top: 10px;">
                Admin Direct Login
            </button>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p>Forgot your password? <a href="forgot_password.php">Reset here</a></p>
                <p>New to the system? <a href="#">Contact administrator</a></p>
            </div>
        </form>
        
        <div class="system-info">
            <h3>ERP System Modules</h3>
            <div class="modules">
                <div class="module academic">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Academic</h4>
                    <p>Course management, student tracking, and exam scheduling</p>
                </div>
                <div class="module finance">
                    <i class="fas fa-chart-line"></i>
                    <h4>Marketing & Finance</h4>
                    <p>Fee management, financial reporting, and campaign tracking</p>
                </div>
                <div class="module hr">
                    <i class="fas fa-users"></i>
                    <h4>Admin & HR</h4>
                    <p>Employee management, payroll, and leave tracking</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>