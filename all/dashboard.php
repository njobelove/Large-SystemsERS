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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ERP System</title>
    <link rel="stylesheet" href="SupperAdmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <?php include 'sidebar.php'; ?>

        <div class="content">
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h1>Welcome to ERP System</h1>
                    <p><?php echo ucfirst($userRole); ?> Dashboard</p>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-tachometer-alt"></i>
                        <h3>Dashboard</h3>
                        <p>System overview</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user"></i>
                        <h3>Profile</h3>
                        <p>Manage your profile</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-cog"></i>
                        <h3>Settings</h3>
                        <p>System settings</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-question-circle"></i>
                        <h3>Help</h3>
                        <p>Get support</p>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="content-section">
                        <h2>System Status</h2>
                        <div class="status-info">
                            <p><strong>User:</strong> <?php echo htmlspecialchars($userData['full_name']); ?></p>
                            <p><strong>Role:</strong> <?php echo ucfirst($userRole); ?></p>
                            <p><strong>Status:</strong> <span class="status-active">Active</span></p>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2>Quick Links</h2>
                        <div class="action-buttons">
                            <a href="#" class="action-btn">
                                <i class="fas fa-home"></i>
                                Home
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-envelope"></i>
                                Messages
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-bell"></i>
                                Notifications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
