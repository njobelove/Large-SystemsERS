<?php
require_once 'auth.php';

$auth = new Auth();
$userData = $auth->getUserData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP System - Admin</title>
    <link rel="stylesheet" href="SupperAdmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="SupperAdmin.php" class="tab <?php echo basename($_SERVER['PHP_SELF']) == 'SupperAdmin.php' ? 'active' : ''; ?>" data-module="all">Dashboard</a>
                    <a href="academic.php" class="tab <?php echo basename($_SERVER['PHP_SELF']) == 'academic.php' ? 'active' : ''; ?>" data-module="academic">Academic</a>
                    <a href="finance.php" class="tab <?php echo basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : ''; ?>" data-module="finance">Marketing & Finance</a>
                    <a href="hr.php" class="tab <?php echo basename($_SERVER['PHP_SELF']) == 'hr.php' ? 'active' : ''; ?>" data-module="hr">Admin & HR</a>
                </div>
            </div>
            
            <div class="header-right">
                <div class="notifications">
                    <button class="icon-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
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
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-content">