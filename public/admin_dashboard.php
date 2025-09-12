<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="p-6">
        <h1 class="text-3xl font-bold mb-6">Welcome, <?php echo $_SESSION['username']; ?> (Admin)</h1>

        <!-- Example cards for metrics -->
        <div class="grid grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded shadow">Total Employees: 50</div>
            <div class="bg-white p-4 rounded shadow">Pending Leaves: 5</div>
            <div class="bg-white p-4 rounded shadow">Assets: 120</div>
        </div>

        <!-- Add links to all admin features -->
        <div class="mt-6 grid grid-cols-3 gap-4">
            <a href="manage_employees.php" class="bg-blue-600 text-white p-4 rounded text-center hover:bg-blue-700">Manage Employees</a>
            <a href="leave_approvals.php" class="bg-green-600 text-white p-4 rounded text-center hover:bg-green-700">Leave Approvals</a>
            <a href="payroll.php" class="bg-yellow-600 text-white p-4 rounded text-center hover:bg-yellow-700">Payroll</a>
            <a href="performance.php" class="bg-purple-600 text-white p-4 rounded text-center hover:bg-purple-700">Performance</a>
            <a href="asset_management.php" class="bg-red-600 text-white p-4 rounded text-center hover:bg-red-700">Assets</a>
        </div>
    </div>
</body>
</html>
