<?php
session_start();
require_once 'auth.php';

$auth = new Auth();
$error = '';

// Check if admin login is being attempted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    // Hardcoded admin credentials (in production, use secure storage)
    $admin_credentials = [
        'username' => 'admin',
        'password' => 'admin123', // Change this in production
        'full_name' => 'System Administrator',
        'role' => 'admin',
        'email' => 'admin@erpsystem.com'
    ];
    
    if ($admin_username === $admin_credentials['username'] && 
        $admin_password === $admin_credentials['password']) {
        // Set admin session manually (consistent with auth.php)
        $_SESSION['user_id'] = 0;
        $_SESSION['username'] = $admin_credentials['username'];
        $_SESSION['full_name'] = $admin_credentials['full_name'];
        $_SESSION['role'] = $admin_credentials['role'];
        $_SESSION['email'] = $admin_credentials['email'];
        
        // Redirect to admin dashboard
        header('Location: SupperAdmin.php');
        exit();
    } else {
        $error = 'Invalid admin credentials';
    }
}

// Regular user login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if ($auth->login($username, $password, $role)) {
        // Redirect to appropriate dashboard based on role
        if ($auth->hasRole('admin')) {
            header('Location: SupperAdmin.php');
        } else if ($auth->hasRole('staff')) {
            header('Location: staff_dashboard.php');
        } else if ($auth->hasRole('student')) {
            header('Location: student_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid login credentials or account not yet approved. Please try again.';
    }
}

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    if ($auth->hasRole('admin')) {
        header('Location: SupperAdmin.php');
    } else if ($auth->hasRole('staff')) {
        header('Location: staff_dashboard.php');
    } else if ($auth->hasRole('student')) {
        header('Location: student_dashboard.php');
    } else {
        header('Location: dashboard.php');
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
        /* Basic styles if login.css is missing */
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
        .admin-login-btn {
            background: #06d6a0;
        }
        .admin-login-btn:hover {
            background: #05c58f;
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
        .admin-login-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #06d6a0;
        }
        .admin-login-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .admin-login-toggle i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }
        .admin-login-form {
            display: none;
            margin-top: 15px;
        }
        .admin-login-form.active {
            display: block;
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
        
        <!-- Admin Login Section -->
        <div class="admin-login-section">
            <div class="admin-login-toggle" onclick="toggleAdminLogin()">
                <i class="fas fa-chevron-down" id="admin-toggle-icon"></i>
                <h3 style="margin: 0;">Administrator Login</h3>
            </div>
            
            <div class="admin-login-form" id="admin-login-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-shield"></i>
                            <input type="text" id="admin_username" name="admin_username" placeholder="Admin username" value="admin">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="password" id="admin_password" name="admin_password" placeholder="Admin password" value="admin123">
                        </div>
                    </div>
                    
                    <button type="submit" name="admin_login" class="login-btn admin-login-btn">Login as Administrator</button>
                </form>
            </div>
        </div>
        
        <!-- Regular User Login -->
        <form method="POST" action="" class="login-form">
            <input type="hidden" name="user_login" value="1">
            <h2>User Login</h2>
            
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

    <script>
        function toggleAdminLogin() {
            const form = document.getElementById('admin-login-form');
            const icon = document.getElementById('admin-toggle-icon');
            
            form.classList.toggle('active');
            
            if (form.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    </script>
</body>
</html>