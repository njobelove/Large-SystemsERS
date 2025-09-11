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

// Fetch calendar events based on role
$events = [];

try {
    if ($userRole === 'admin' || $userRole === 'staff') {
        // Fetch all events for admin and staff
        $stmt = $pdo->query("SELECT id, title, description, start_date, end_date FROM events ORDER BY start_date ASC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($userRole === 'student') {
        // Fetch events relevant to the student (e.g., enrolled courses)
        // For simplicity, fetch all events for now
        $stmt = $pdo->query("SELECT id, title, description, start_date, end_date FROM events ORDER BY start_date ASC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching calendar events: " . $e->getMessage());
    $events = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Calendar - ERP System</title>
    <link rel="stylesheet" href="SupperAdmin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .calendar-container {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        .calendar-header {
            margin-bottom: 30px;
        }
        .calendar-header h1 {
            color: #4361ee;
        }
        .event-list {
            list-style: none;
            padding: 0;
        }
        .event-item {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        .event-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #4361ee;
        }
        .event-dates {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 10px;
        }
        .event-description {
            font-size: 1rem;
            color: #555;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-content">
        <?php include 'sidebar.php'; ?>
        <div class="content">
            <div class="calendar-container">
                <div class="calendar-header">
                    <h1>Calendar</h1>
                    <p>Upcoming events and important dates</p>
                </div>
                <?php if (!empty($events)): ?>
                    <ul class="event-list">
                        <?php foreach ($events as $event): ?>
                            <li class="event-item">
                                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="event-dates">
                                    <?php 
                                        $start = date('M j, Y', strtotime($event['start_date']));
                                        $end = date('M j, Y', strtotime($event['end_date']));
                                        echo $start === $end ? $start : "$start - $end";
                                    ?>
                                </div>
                                <div class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming events found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
