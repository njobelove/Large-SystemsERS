<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff'){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="p-6">
        <h1 class="text-3xl font-bold mb-6">Welcome, <?php echo $_SESSION['username']; ?> (Staff)</h1>

        <!-- Example personal info cards -->
        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white p-4 rounded shadow">Your Attendance: 95%</div>
            <div class="bg-white p-4 rounded shadow">Leaves Pending: 2</div>
            <div class="bg-white p-4 rounded shadow">Performance Rating: 4.2 / 5</div>
        </div>

        <!-- Links for self-service -->
        <div class="mt-6 grid grid-cols-2 gap-4">
            <a href="my_attendance.php" class="bg-blue-600 text-white p-4 rounded text-center hover:bg-blue-700">View Attendance</a>
            <a href="submit_leave.php" class="bg-green-600 text-white p-4 rounded text-center hover:bg-green-700">Submit Leave</a>
            <a href="my_performance.php" class="bg-purple-600 text-white p-4 rounded text-center hover:bg-purple-700">Performance</a>
        </div>
    </div>
</body>
</html>
