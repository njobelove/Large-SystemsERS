<?php
require_once 'auth.php';

$auth = new Auth();
$userData = $auth->getUserData();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
            <!-- Sidebar -->
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <div class="nav-section">
                        <h3>Main</h3>
                        <a href="SupperAdmin.php" class="nav-link <?php echo $currentPage == 'SupperAdmin.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="overview.php" class="nav-link <?php echo $currentPage == 'overview.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Overview</span>
                        </a>
                        <a href="calendar.php" class="nav-link <?php echo $currentPage == 'calendar.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendar</span>
                        </a>
                    </div>
                    
                    <div class="nav-section">
                        <h3>Academic</h3>
                        <a href="courses.php" class="nav-link <?php echo $currentPage == 'courses.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book"></i>
                            <span>Courses</span>
                        </a>
                        <a href="students.php" class="nav-link <?php echo $currentPage == 'students.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                        <a href="faculty.php" class="nav-link <?php echo $currentPage == 'faculty.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Faculty</span>
                        </a>
                        <a href="exams.php" class="nav-link <?php echo $currentPage == 'exams.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Exams</span>
                        </a>
                    </div>
                    
                    <div class="nav-section">
                        <h3>Finance</h3>
                        <a href="fees.php" class="nav-link <?php echo $currentPage == 'fees.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Tuition Fees</span>
                        </a>
                        <a href="financial_reports.php" class="nav-link <?php echo $currentPage == 'financial_reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i>
                            <span>Financial Reports</span>
                        </a>
                        <a href="marketing.php" class="nav-link <?php echo $currentPage == 'marketing.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bullhorn"></i>
                            <span>Marketing</span>
                        </a>
                    </div>
                    
                    <div class="nav-section">
                        <h3>Admin & HR</h3>
                        <a href="employees.php" class="nav-link <?php echo $currentPage == 'employees.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Employees</span>
                        </a>
                        <a href="payroll.php" class="nav-link <?php echo $currentPage == 'payroll.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-check"></i>
                            <span>Payroll</span>
                        </a>
                        <a href="leave_management.php" class="nav-link <?php echo $currentPage == 'leave_management.php' ? 'active' : ''; ?>">
                            <i class="fas fa-business-time"></i>
                            <span>Leave Management</span>
                        </a>
                        <a href="assets.php" class="nav-link <?php echo $currentPage == 'assets.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cube"></i>
                            <span>Assets</span>
                        </a>
                        <?php if ($auth->hasRole('admin')): ?>
                        <a href="admin_approval.php" class="nav-link <?php echo $currentPage == 'admin_approval.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-check"></i>
                            <span>User Approvals</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </aside>