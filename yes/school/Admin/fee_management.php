<?php
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

// Handle form submissions for add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_fee'])) {
        $fee_name = $_POST['fee_name'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        if ($fee_name && is_numeric($amount)) {
            $stmt = $conn->prepare("INSERT INTO fixed_fees (fee_name, amount) VALUES (?, ?)");
            $stmt->bind_param('sd', $fee_name, $amount);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['edit_fee'])) {
        $id = $_POST['id'] ?? 0;
        $fee_name = $_POST['fee_name'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        if ($id && $fee_name && is_numeric($amount)) {
            $stmt = $conn->prepare("UPDATE fixed_fees SET fee_name = ?, amount = ? WHERE id = ?");
            $stmt->bind_param('sdi', $fee_name, $amount, $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['delete_fee'])) {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM fixed_fees WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: fee_management.php');
    exit;
}

// Fetch all fixed fees
$result = $conn->query("SELECT * FROM fixed_fees ORDER BY fee_name ASC");
$fees = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Fee Management</title>
    <link rel="stylesheet" href="../css/new_style.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 20px;
            color: #222;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 2.5rem;
        }
        h2 {
            margin-top: 40px;
            margin-bottom: 20px;
            color: #34495e;
            font-weight: 600;
            font-size: 1.5rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1rem;
            color: #34495e;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="number"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            margin-right: 15px;
            transition: background-color 0.3s ease;
        }
        .btn-add {
            background-color: #27ae60;
            color: white;
        }
        .btn-add:hover {
            background-color: #219150;
        }
        .btn-edit {
            background-color: #2980b9;
            color: white;
        }
        .btn-edit:hover {
            background-color: #1f6391;
        }
        .btn-delete {
            background-color: #c0392b;
            color: white;
        }
        .btn-delete:hover {
            background-color: #962d22;
        }
        .fee-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9fbfd;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s ease;
        }
        .fee-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .fee-card form {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .fee-card input {
            flex: 1;
            padding: 10px 12px;
            font-size: 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            transition: border-color 0.3s ease;
        }
        .fee-card input:focus {
            border-color: #3498db;
            outline: none;
        }
        .actions {
            display: flex;
            gap: 12px;
        }
        .back-link {
            text-align: center;
            margin-top: 40px;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        p.no-fees {
            text-align: center;
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fee Management</h1>

        <h2>Add New Fee</h2>
        <form method="post" action="fee_management.php">
            <input type="hidden" name="add_fee" value="1" />
            <div class="form-group">
                <label for="fee_name">Fee Name:</label>
                <input type="text" id="fee_name" name="fee_name" required />
            </div>
            <div class="form-group">
                <label for="amount">Amount (CFA):</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0" required />
            </div>
            <button type="submit" class="btn btn-add">Add Fee</button>
        </form>

        <h2>Existing Fees</h2>
        <?php if (!empty($fees)): ?>
            <?php foreach ($fees as $fee): ?>
                <div class="fee-card">
                    <form method="post" action="fee_management.php">
                        <input type="text" name="fee_name" value="<?= htmlspecialchars($fee['fee_name']) ?>" required />
                        <input type="number" name="amount" value="<?= number_format($fee['amount'], 2, '.', '') ?>" step="0.01" min="0" required />
                        <div class="actions">
                            <input type="hidden" name="id" value="<?= $fee['id'] ?>" />
                            <button type="submit" name="edit_fee" class="btn btn-edit">Save</button>
                            <button type="submit" name="delete_fee" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this fee?');">Delete</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-fees">No fees found.</p>
        <?php endif; ?>

        <div class="back-link">
            <a href="assign_fees.php">Assign Fees to Students</a> | 
            <a href="dashboard.php">Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
