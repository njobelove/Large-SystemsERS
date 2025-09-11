<?php
require_once '../include/auth_check.php';
checkRoleAccess(['marketing']); // Allow only 'marketing' role

require_once '../include/db_connect.php';

// You can add marketing-specific data fetching here later

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketing Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- Assuming a shared stylesheet -->
</head>
<body>
    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! (Marketing)</h1>
        <p>This is the marketing dashboard.</p>
        <nav>
            <a href="list_campaigns.php">Manage Campaigns</a>
            <a href="list_leads.php">Manage Leads</a>
            <a href="list_conversions.php">Manage Conversions</a>
            <a href="/school/logout.php">Logout</a>
        </nav>
    </div>
</body>
</html>