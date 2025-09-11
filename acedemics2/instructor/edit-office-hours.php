<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Ensure user is instructor
if (!isInstructor()) {
    header('Location: ../index.html');
    exit();
}
*/
$user = getCurrentUser();
$userId = getCurrentUserId();

// Get current office hours (simulated - in real app this would come from database)
$currentOfficeHours = [
    'monday' => ['start' => '14:00', 'end' => '16:00', 'location' => 'Room 101'],
    'tuesday' => ['start' => '10:00', 'end' => '12:00', 'location' => 'Room 101'],
    'wednesday' => ['start' => '', 'end' => '', 'location' => ''],
    'thursday' => ['start' => '15:00', 'end' => '17:00', 'location' => 'Room 101'],
    'friday' => ['start' => '11:00', 'end' => '13:00', 'location' => 'Room 101']
];

// Handle office hours update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_office_hours'])) {
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    foreach ($days as $day) {
        $start = $_POST[$day . '_start'] ?? '';
        $end = $_POST[$day . '_end'] ?? '';
        $location = sanitizeInput($_POST[$day . '_location'] ?? '');

        // In a real application, this would update the database
        $currentOfficeHours[$day] = [
            'start' => $start,
            'end' => $end,
            'location' => $location
        ];
    }

    $message = 'Office hours updated successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Office Hours - Instructor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="#" onclick="logout()">Logout</a></li>
            </ul>
            <div class="user-info">
                <img src="../images/user-avatar.png" alt="User">
                <span><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="attendance.php">Attendance</a></li>
            <li><a href="gradebook.php">Gradebook</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="course-materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Edit Office Hours</h2>
        <p>Set your office hours for student consultations.</p>

        <?php if ($message): ?>
            <div class="card" style="margin-bottom: 20px; background-color: #e8f5e8; border-left: 5px solid #4caf50;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Current Office Hours</h3>
            <form method="post">
                <input type="hidden" name="update_office_hours" value="1">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <?php
                    $days = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday'
                    ];

                    foreach ($days as $key => $dayName):
                    ?>
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h4><?php echo htmlspecialchars($dayName); ?></h4>

                        <div style="margin-bottom: 10px;">
                            <label for="<?php echo $key; ?>_start">Start Time:</label><br>
                            <input type="time" id="<?php echo $key; ?>_start" name="<?php echo $key; ?>_start"
                                   value="<?php echo htmlspecialchars($currentOfficeHours[$key]['start']); ?>"
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label for="<?php echo $key; ?>_end">End Time:</label><br>
                            <input type="time" id="<?php echo $key; ?>_end" name="<?php echo $key; ?>_end"
                                   value="<?php echo htmlspecialchars($currentOfficeHours[$key]['end']); ?>"
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label for="<?php echo $key; ?>_location">Location:</label><br>
                            <input type="text" id="<?php echo $key; ?>_location" name="<?php echo $key; ?>_location"
                                   value="<?php echo htmlspecialchars($currentOfficeHours[$key]['location']); ?>"
                                   placeholder="e.g., Room 101"
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <?php if ($currentOfficeHours[$key]['start'] && $currentOfficeHours[$key]['end']): ?>
                        <div style="background: #e8f5e8; padding: 8px; border-radius: 4px; margin-top: 10px;">
                            <strong>Current:</strong> <?php echo htmlspecialchars($currentOfficeHours[$key]['start']); ?> - <?php echo htmlspecialchars($currentOfficeHours[$key]['end']); ?>
                            <?php if ($currentOfficeHours[$key]['location']): ?>
                            at <?php echo htmlspecialchars($currentOfficeHours[$key]['location']); ?>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div style="background: #fff3e0; padding: 8px; border-radius: 4px; margin-top: 10px;">
                            <em>No office hours set</em>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" style="margin-top: 20px; background: #4caf50; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px;">Update Office Hours</button>
            </form>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Office Hours Guidelines</h3>
            <ul>
                <li>Office hours should be consistent and predictable for students</li>
                <li>Consider your teaching schedule when setting office hours</li>
                <li>Provide a specific location where students can find you</li>
                <li>Update office hours promptly if your schedule changes</li>
                <li>Students will be able to view your office hours on their dashboards</li>
            </ul>
        </div>
    </main>
</body>
</html>
