<?php
session_start();
require_once 'auth.php';
require_once 'config.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    // Redirect non-admin users to their appropriate dashboard
    if ($auth->hasRole('staff')) {
        header('Location: staff_dashboard.php');
    } elseif ($auth->hasRole('student')) {
        header('Location: student_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit();
}

// Get user data
$userData = $auth->getUserData();

// Database connection for fetching data
$pdo = getDBConnection();

// Fetch admin-specific stats
$stats = [];
$recentActivities = [];
$notifications = [];

// Admin sees all stats
$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student' AND registration_status = 'approved'");
$stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

$stmt = $pdo->query("SELECT COUNT(*) as total_staff FROM users WHERE role = 'staff' AND registration_status = 'approved'");
$stats['total_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_approvals FROM users WHERE registration_status = 'pending'");
$stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_approvals'];

$stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
$stats['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];

// Recent pending registrations
try {
    // Check if registration_requests table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'registration_requests'");
    $tableExists = $tableCheck->rowCount() > 0;

    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT u.full_name, u.username, u.role, r.requested_at
            FROM users u
            JOIN registration_requests r ON u.id = r.user_id
            WHERE u.registration_status = 'pending'
            ORDER BY r.requested_at DESC
            LIMIT 5
        ");
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: get recent pending users without registration_requests table
        $stmt = $pdo->query("
            SELECT u.full_name, u.username, u.role, u.created_at as requested_at
            FROM users u
            WHERE u.registration_status = 'pending'
            ORDER BY u.created_at DESC
            LIMIT 5
        ");
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    error_log("Database error fetching recent activities: " . $e->getMessage());
    $recentActivities = [];
}

// Notifications
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE registration_status = 'pending'");
$pending_approvals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($pending_approvals > 0) {
    $notifications[] = "$pending_approvals registration requests pending approval";
}

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a56d4;
            --accent: #06d6a0;
            --light: #f8f9fa;
            --dark: #343a40;
            --danger: #e63946;
            --warning: #ffd166;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--dark);
        }
        
        .header-center {
            flex: 1;
            max-width: 600px;
            margin: 0 20px;
        }
        
        .module-tabs {
            display: flex;
            background: #f0f2f5;
            border-radius: 8px;
            padding: 4px;
        }
        
        .tab {
            padding: 8px 16px;
            border: none;
            background: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .tab.active {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .icon-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--dark);
            position: relative;
        }
        
        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: bold;
        }
        
        .user-role {
            font-size: 0.8rem;
            color: #777;
        }
        
        .logout-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--dark);
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-nav {
            padding: 0 15px;
        }
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-section h3 {
            padding: 10px 15px;
            font-size: 0.9rem;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--primary);
            color: white;
        }
        
        .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        
        .nav-badge {
            position: absolute;
            right: 15px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
        }
        
        .content {
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .breadcrumb {
            color: #777;
            font-size: 0.9rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .approval-card {
            border: 2px solid var(--accent);
            background: linear-gradient(135deg, #fff, #e8f5e9);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }
        
        .academic { background: #e7f4ff; color: #4361ee; }
        .finance { background: #fff2e5; color: #fd7e14; }
        .hr { background: #e6f7f0; color: #06d6a0; }
        .info { background: #f8e8ff; color: #9b5de5; }
        .approval { background: #fff0f0; color: #e63946; }
        
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #777;
            font-size: 14px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .action-btn h3 {
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .action-btn p {
            color: #777;
            font-size: 0.9rem;
        }
        
        /* Data Section */
        .data-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container, .recent-activities {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: var(--primary);
        }
        
        .period-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .chart-placeholder {
            height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            border-radius: 8px;
            color: #777;
        }
        
        .chart-placeholder i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .activities-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            background: #f5f7fb;
            color: var(--primary);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content h4 {
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .activity-content p {
            color: #777;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.75rem;
        }
        
        /* Modules Status */
        .modules-status {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .module-status-card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            position: relative;
        }
        
        .module-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .module-header i {
            font-size: 24px;
            margin-right: 10px;
            color: var(--primary);
        }
        
        .module-header h3 {
            color: var(--primary);
        }
        
        .module-status-card p {
            color: #777;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .status-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .data-section {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-center {
                margin: 0;
                width: 100%;
            }
            
            .stats-grid, .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    <span>ERP System</span>
                </div>
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="header-center">
                <div class="module-tabs">
                    <button class="tab active" data-module="all">Dashboard</button>
                    <button class="tab" data-module="academic">Academic</button>
                    <button class="tab" data-module="finance">Marketing & Finance</button>
                    <button class="tab" data-module="hr">Admin & HR</button>
                </div>
            </div>
            
            <div class="header-right">
                <div class="notifications">
                    <button class="icon-btn">
                        <i class="fas fa-bell"></i>
                        <?php if (!empty($notifications)): ?>
                            <span class="badge"><?php echo count($notifications); ?></span>
                                                    <?php endif; ?>
                    </button>
                </div>
                <div class="user-menu">
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userData['full_name']); ?>&background=4361ee&color=fff" alt="User">
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($userData['full_name']); ?></span>
                        <span class="user-role"><?php echo ucfirst($userData['role']); ?></span>
                    </div>
                    <a href="?logout=true" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3>Main</h3>
                    <a href="SupperAdmin.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="overview.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Overview</span>
                    </a>
                    <a href="calendar.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Calendar</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <h3>Academic</h3>
                    <a href="#" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Faculty</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Exams</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <h3>Finance</h3>
                    <a href="#" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Tuition Fees</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-pie"></i>
                        <span>Financial Reports</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-bullhorn"></i>
                        <span>Marketing</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <h3>Admin & HR</h3>
                    <a href="admin_approval.php" class="nav-link">
                        <i class="fas fa-user-check"></i>
                        <span>User Approvals</span>
                        <?php if ($stats['pending_approvals'] > 0): ?>
                            <span class="nav-badge"><?php echo $stats['pending_approvals']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Employees</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-money-check"></i>
                        <span>Payroll</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-business-time"></i>
                        <span>Leave Management</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-cube"></i>
                        <span>Assets</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content">
                <div class="page-header">
                    <h1>Admin Dashboard</h1>
                    <div class="breadcrumb">
                        <span>Home</span> / <span>Dashboard</span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card academic">
                        <div class="stat-icon academic">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_students']; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card finance">
                        <div class="stat-icon finance">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_staff']; ?></h3>
                            <p>Total Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card hr">
                        <div class="stat-icon hr">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_courses']; ?></h3>
                            <p>Total Courses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card approval">
                        <div class="stat-icon info">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_approvals']; ?></h3>
                            <p>Pending Approvals</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="admin_approval.php" class="action-btn">
                        <i class="fas fa-user-check"></i>
                        <h3>Approve Users</h3>
                        <p>Review and approve pending registrations</p>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Add Course</h3>
                        <p>Create a new course</p>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        <h3>View Reports</h3>
                        <p>Generate system reports</p>
                    </a>
                    <a href="#" class="action-btn">
                        <i class="fas fa-cog"></i>
                        <h3>Settings</h3>
                        <p>System configuration</p>
                    </a>
                </div>

                <!-- Charts and Data Section -->
                <div class="data-section">
                    <div class="chart-container">
                        <div class="section-header">
                            <h2>System Overview</h2>
                            <select class="period-select">
                                <option>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>Last 90 Days</option>
                            </select>
                        </div>
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-bar"></i>
                            <p>System statistics chart would be displayed here</p>
                        </div>
                    </div>
                    
                    <div class="recent-activities">
                        <div class="section-header">
                            <h2>Pending Approvals</h2>
                            <a href="admin_approval.php" class="view-all">View All</a>
                        </div>
                        <div class="activities-list">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h4><?php echo htmlspecialchars($activity['full_name']); ?></h4>
                                            <p><?php echo ucfirst($activity['role']); ?> - <?php echo htmlspecialchars($activity['username']); ?></p>
                                            <span class="activity-time">
                                                <?php echo date('M j, Y g:i A', strtotime($activity['requested_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <p>No pending approvals</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modules Status -->
                <div class="modules-status">
                    <div class="section-header">
                        <h2>System Modules Status</h2>
                    </div>
                    <div class="modules-grid">
                        <div class="module-status-card">
                            <div class="module-header">
                                <i class="fas fa-graduation-cap"></i>
                                <h3>Academic Module</h3>
                            </div>
                            <p>Course Management, Student Tracking, Exam Scheduling</p>
                            <div class="status-indicator active">Active</div>
                        </div>
                        
                        <div class="module-status-card">
                            <div class="module-header">
                                <i class="fas fa-chart-line"></i>
                                <h3>Finance Module</h3>
                            </div>
                            <p>Fee Management, Financial Reporting, Campaign Tracking</p>
                            <div class="status-indicator active">Active</div>
                        </div>
                        
                        <div class="module-status-card">
                            <div class="module-header">
                                <i class="fas fa-users"></i>
                                <h3>HR Module</h3>
                            </div>
                            <p>Employee Management, Payroll, Leave Tracking</p>
                            <div class="status-indicator active">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // In a real app, this would load the appropriate module content
                console.log('Switched to module:', this.dataset.module);
            });
        });
        
        // Sidebar toggle for mobile
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('open');
            });
        }
        
        // Notification badge click
        const notificationBtn = document.querySelector('.notifications .icon-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', function() {
                window.location.href = 'admin_approval.php';
            });
        }
    </script>
</body>
</html>