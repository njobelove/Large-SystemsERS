<?php
session_start();
require_once 'auth.php';

$auth = new Auth();

// Check if user is logged in and is a student
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) {
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
    <title>Student Dashboard - ERP System</title>
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
                    <p>Student Dashboard - ERP System</p>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-book"></i>
                        <h3>My Courses</h3>
                        <p>View enrolled courses</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar"></i>
                        <h3>Schedule</h3>
                        <p>Class timetable</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <h3>Grades</h3>
                        <p>View your grades</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>Fees</h3>
                        <p>Fee payment status</p>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="content-section">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="#" class="action-btn">
                                <i class="fas fa-eye"></i>
                                View Courses
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-calendar"></i>
                                Check Schedule
                            </a>
                            <a href="#" class="action-btn">
                                <i class="fas fa-chart-bar"></i>
                                View Grades
                            </a>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2>Recent Activities</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <i class="fas fa-book-open"></i>
                                <div class="activity-content">
                                    <p>New course material uploaded</p>
                                    <span>1 hour ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <i class="fas fa-calendar-check"></i>
                                <div class="activity-content">
                                    <p>Assignment deadline approaching</p>
                                    <span>2 hours ago</span>
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
