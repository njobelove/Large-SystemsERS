<?php
session_start();
require_once 'auth.php';

$auth = new Auth();

// Check if user is logged in and is a staff member
if (!$auth->isLoggedIn() || !$auth->hasRole('staff')) {
    header('Location: login.php');
    exit();
}

$userData = $auth->getUserData();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - ERP System</title>
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
                    <h1>Welcome, <?php echo htmlspecialchars($userData['full_name']); ?>!</h1>
                    <p>Staff Dashboard - ERP System</p>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3>Students</h3>
                        <p>Manage student records</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-book"></i>
                        <h3>Courses</h3>
                        <p>Course management</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar"></i>
                        <h3>Schedule</h3>
                        <p>Class scheduling</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Reports</h3>
                        <p>Generate reports</p>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="content-section">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="#" class="action-btn">
                                <i class="fas fa-plus"></i>
                                Add Student
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-calendar-plus"></i>
                                Schedule Class
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-file-alt"></i>
                                Generate Report
                            </a>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2>Recent Activities</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <i class="fas fa-user-plus"></i>
                                <div class="activity-content">
                                    <p>New student registered</p>
                                    <span>2 hours ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <i class="fas fa-calendar-check"></i>
                                <div class="activity-content">
                                    <p>Class scheduled for tomorrow</p>
                                    <span>4 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
