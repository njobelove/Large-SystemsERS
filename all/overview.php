<?php
session_start();
require_once 'auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userData = $auth->getUserData();
$userRole = $userData['role'];

// Database connection
$pdo = getDBConnection();

// Fetch overview data based on role
$overviewData = [];

try {
    if ($userRole === 'admin') {
        // Admin overview data
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student' AND registration_status = 'approved'");
        $overviewData['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

        $stmt = $pdo->query("SELECT COUNT(*) as total_staff FROM users WHERE role = 'staff' AND registration_status = 'approved'");
        $overviewData['total_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];

        $stmt = $pdo->query("SELECT COUNT(*) as pending_approvals FROM users WHERE registration_status = 'pending'");
        $overviewData['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_approvals'];

        $stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
        $overviewData['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
    } elseif ($userRole === 'staff') {
        // Staff overview data
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student' AND registration_status = 'approved'");
        $overviewData['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

        $stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
        $overviewData['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
    } elseif ($userRole === 'student') {
        // Student overview data
        $overviewData['welcome_message'] = "Welcome to your dashboard, " . htmlspecialchars($userData['full_name']) . "!";
    }
} catch (PDOException $e) {
    error_log("Error fetching overview data: " . $e->getMessage());
    $overviewData = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Overview - ERP System</title>
    <link rel="stylesheet" href="SupperAdmin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .overview-container {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .overview-header {
            margin-bottom: 30px;
        }
        .overview-header h1 {
            color: #4361ee;
        }
        .overview-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            flex: 1 1 200px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        .card i {
            font-size: 36px;
            color: #4361ee;
            margin-bottom: 10px;
        }
        .card h3 {
            margin-bottom: 5px;
            font-size: 1.5rem;
        }
        .card p {
            color: #777;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-content">
        <?php include 'sidebar.php'; ?>
        <div class="content">
            <div class="overview-container">
                <div class="overview-header">
                    <h1>Overview</h1>
                    <p>Summary of your system status</p>
                </div>
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                <div class="overview-cards">
                    <?php if (isset($overviewData['total_students'])): ?>
                    <div class="card">
                        <i class="fas fa-user-graduate"></i>
                        <h3><?php echo $overviewData['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($overviewData['total_staff'])): ?>
                    <div class="card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $overviewData['total_staff']; ?></h3>
                        <p>Total Staff</p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($overviewData['total_courses'])): ?>
                    <div class="card">
                        <i class="fas fa-book"></i>
                        <h3><?php echo $overviewData['total_courses']; ?></h3>
                        <p>Total Courses</p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($overviewData['pending_approvals'])): ?>
                    <div class="card">
                        <i class="fas fa-user-clock"></i>
                        <h3><?php echo $overviewData['pending_approvals']; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php elseif ($userRole === 'student'): ?>
                    <p><?php echo $overviewData['welcome_message'] ?? 'Welcome!'; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
