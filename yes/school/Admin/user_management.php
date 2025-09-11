<?php
session_start();
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

// --- Filtering and Pagination ---
$username_filter = isset($_GET['username']) ? trim($_GET['username']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// --- Build Query Conditions ---
$conditions = [];
$params = [];
$types = '';

if ($username_filter !== '') {
    $conditions[] = 'username LIKE ?';
    $types .= 's';
    $params[] = '%' . $username_filter . '%';
}
if ($role_filter !== '') {
    $conditions[] = 'role = ?';
    $types .= 's';
    $params[] = $role_filter;
}

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = ' WHERE ' . implode(' AND ', $conditions);
}

// --- Count Total Users for Pagination ---
$countSql = "SELECT COUNT(id) AS total FROM users" . $whereSql;
$stmtCount = $conn->prepare($countSql);
if ($types !== '') {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$countResult = $stmtCount->get_result()->fetch_assoc();
$totalRows = $countResult['total'] ?? 0;
$totalPages = ceil($totalRows / $perPage);
$stmtCount->close();

// --- Fetch Users for Current Page ---
$sql = "SELECT id, username, role, created_at FROM users" . $whereSql . " ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$offset, $perPage]);

if ($types !== '') {
    $stmt->bind_param($queryTypes, ...$queryParams);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}

$stmt->execute();
$result = $stmt->get_result();

// Helper function for pagination links
function build_query($overrides = []) {
    return http_build_query(array_merge($_GET, $overrides));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4a90e2, #50e3c2);
            margin: 0;
            padding: 20px;
            color: #fff;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        h1 {
            color: #e0f7fa;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .success-message {
            background-color: #a7ffeb;
            color: #004d40;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #4a90e2;
            text-shadow: 0 1px 2px rgba(0,77,64,0.5);
        }
        a.button, button {
            background: linear-gradient(45deg, #4a90e2, #50e3c2);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            box-shadow: 0 4px 12px rgba(74,144,226,0.6);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        a.button:hover, button:hover {
            background: linear-gradient(45deg, #357ABD, #3bb9a9);
            box-shadow: 0 6px 16px rgba(53,122,189,0.8);
            color: #e0f2f1;
        }
        form.filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        form.filter-form input[type="text"],
        form.filter-form select {
            padding: 8px 12px;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            font-size: 14px;
            flex-grow: 1;
            min-width: 150px;
            background: rgba(255, 255, 255, 0.15);
            color: #e0f7fa;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
            transition: background 0.3s ease;
        }
        form.filter-form input[type="text"]::placeholder,
        form.filter-form select::placeholder {
            color: #b2dfdb;
        }
        form.filter-form input[type="text"]:focus,
        form.filter-form select:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
        }
        form.filter-form button,
        form.filter-form a {
            flex-shrink: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        table thead {
            background: linear-gradient(45deg, #4a90e2, #50e3c2);
            color: white;
        }
        table th, table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: left;
            color: #e0f7fa;
        }
        table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.05);
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #a7ffeb;
        }
        .pagination a {
            color: #4a90e2;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 12px rgba(74,144,226,0.6);
        }
        .pagination a:hover {
            background-color: #4a90e2;
            color: white;
            box-shadow: 0 6px 16px rgba(74,144,226,0.8);
        }
        .pagination span {
            font-weight: bold;
            color: #a7ffeb;
        }
        p.back-link {
            text-align: center;
        }
        p.back-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,77,64,0.5);
        }
        p.back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Management</h1>

        <?php if (isset($_GET['message'])): ?>
            <p class="success-message"><?= htmlspecialchars($_GET['message']) ?></p>
        <?php endif; ?>

        <p><a href="create_user.php" class="button">Create New User</a></p>

        <!-- Filter Form -->
        <form method="GET" action="user_management.php" class="filter-form">
            <input type="text" name="username" placeholder="Filter by username..." value="<?= htmlspecialchars($username_filter) ?>">
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="finance" <?= $role_filter === 'finance' ? 'selected' : '' ?>>Finance</option>
                <option value="marketing" <?= $role_filter === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
            </select>
            <button type="submit">Filter</button>
            <a href="user_management.php" class="button">Reset</a>
        </form>

        <!-- Users Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="button">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="button" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= build_query(['page' => $page - 1]) ?>">&laquo; Previous</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?<?= build_query(['page' => $page + 1]) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>

        <p class="back-link"><a href="dashboard.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
