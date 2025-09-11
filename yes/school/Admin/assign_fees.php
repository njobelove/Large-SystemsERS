<?php
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

// Handle form submission to assign fees to students
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? 0;
    $fee_ids = $_POST['fee_id'] ?? [];
    $amounts = $_POST['amount'] ?? [];

    if ($student_id && is_array($fee_ids) && is_array($amounts)) {
        // Delete existing assignments for the student
        $stmt = $conn->prepare("DELETE FROM student_fees WHERE student_id = ?");
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $stmt->close();

        // Insert new assignments
        $stmt = $conn->prepare("INSERT INTO student_fees (student_id, fixed_fee_id, amount) VALUES (?, ?, ?)");
        foreach ($fee_ids as $index => $fee_id) {
            $amount = floatval($amounts[$index]);
            if ($fee_id && $amount >= 0) {
                $stmt->bind_param('iid', $student_id, $fee_id, $amount);
                $stmt->execute();
            }
        }
        $stmt->close();

        header('Location: assign_fees.php?success=1');
        exit;
    }
}

// Fetch all students
$students = [];
$result = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $result->free();
}

// Fetch all fixed fees
$fees = [];
$result = $conn->query("SELECT * FROM fixed_fees ORDER BY fee_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    $result->free();
}

// Fetch assigned fees for selected student if any
$assigned_fees = [];
$selected_student_id = $_GET['student_id'] ?? 0;
if ($selected_student_id) {
    $stmt = $conn->prepare("SELECT fixed_fee_id, amount FROM student_fees WHERE student_id = ?");
    $stmt->bind_param('i', $selected_student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $assigned_fees[$row['fixed_fee_id']] = $row['amount'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Fees to Students</title>
    <link rel="stylesheet" href="../css/new_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fefefe;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #4a90e2;
        }
        form {
            margin-top: 20px;
        }
        select, input[type="number"] {
            padding: 8px;
            font-size: 14px;
            margin-bottom: 15px;
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
    <script>
        function onStudentChange() {
            const studentId = document.getElementById('student_id').value;
            window.location.href = 'assign_fees.php?student_id=' + studentId;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Assign Fees to Students</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Fees assigned successfully.</div>
        <?php endif; ?>

        <label for="student_id">Select Student:</label>
        <select id="student_id" name="student_id" onchange="onStudentChange()">
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= $student['id'] ?>" <?= ($student['id'] == $selected_student_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selected_student_id): ?>
            <form method="post" action="assign_fees.php">
                <input type="hidden" name="student_id" value="<?= $selected_student_id ?>">
                <table>
                    <thead>
                        <tr>
                            <th>Fee Name</th>
                            <th>Amount (CFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fees as $fee): ?>
                            <tr>
                                <td><?= htmlspecialchars($fee['fee_name']) ?></td>
                                <td>
                                    <input type="hidden" name="fee_id[]" value="<?= $fee['id'] ?>">
                                    <input type="number" name="amount[]" step="0.01" min="0" value="<?= isset($assigned_fees[$fee['id']]) ? $assigned_fees[$fee['id']] : number_format($fee['amount'], 2, '.', '') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn">Assign Fees</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
