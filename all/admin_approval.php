<?php
session_start();
require_once 'auth.php';
require_once 'config.php';

$auth = new Auth();

// Only admins can access this page
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle approval or rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $action = $_GET['action'];

    try {
        if ($action === 'approve') {
            // Start transaction
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET registration_status = 'approved' WHERE id = :id");
            $stmt->bindParam(':id', $user_id);

            if ($stmt->execute()) {
                // Update registration request only if admin_id is valid
                $admin_id = $auth->getUserId();
                if ($admin_id && $admin_id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE registration_requests
                        SET approved_by = :admin_id, approved_at = NOW()
                        WHERE user_id = :user_id
                    ");
                    $stmt->bindParam(':admin_id', $admin_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                }

                // Send approval message to user
                try {
                    $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = :id");
                    $stmt->bindParam(':id', $user_id);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && !empty($user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                        $to = $user['email'];
                        $subject = "Admission Confirmation - ICT University";
                        $messageBody = "Dear " . $user['full_name'] . ",\n\nCongratulations! You have been admitted at the ICT University.\n\nYou can now log in to the university portal using your credentials.\n\nWelcome to ICT University!\n\nBest regards,\nAdmissions Office\nICT University";
                        $headers = "From: njobeloveline.nkeni@ictuniversity.edu.cm\r\n";
                        $headers .= "Reply-To: njobeloveline.nkeni@ictuniversity.edu.cm\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                        if (mail($to, $subject, $messageBody, $headers)) {
                            error_log("Approval email sent successfully to: " . $to);
                        } else {
                            error_log("Failed to send approval email to: " . $to);
                        }
                    } else {
                        error_log("Invalid or missing email for user ID: " . $user_id);
                    }
                } catch (Exception $e) {
                    error_log("Error sending approval email: " . $e->getMessage());
                }

                $pdo->commit();
                $message = 'User approved successfully.';
            } else {
                $pdo->rollBack();
                $message = 'Error approving user.';
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET registration_status = 'rejected' WHERE id = :id");
            $stmt->bindParam(':id', $user_id);

            if ($stmt->execute()) {
                $message = 'User rejected successfully.';
            } else {
                $message = 'Error rejecting user.';
            }
        }
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error in admin_approval.php: " . $e->getMessage());
        $message = 'Database error occurred. Please try again.';
    }
}

// Get pending registration requests
try {
    // First check if registration_requests table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'registration_requests'");
    $tableExists = $tableCheck->rowCount() > 0;

    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT u.*, r.requested_at
            FROM users u
            JOIN registration_requests r ON u.id = r.user_id
            WHERE u.registration_status = 'pending'
            ORDER BY r.requested_at DESC
        ");
        $pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: get pending users without registration_requests table
        $stmt = $pdo->query("
            SELECT u.*, u.created_at as requested_at
            FROM users u
            WHERE u.registration_status = 'pending'
            ORDER BY u.created_at DESC
        ");
        $pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("registration_requests table not found, using fallback query");
    }
} catch(PDOException $e) {
    error_log("Database error fetching pending users: " . $e->getMessage());
    $pending_users = [];
    $message = 'Error loading pending registration requests. Please check database configuration.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approval - ERP System</title>
    <link rel="stylesheet" href="SupperAdmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-details h3 {
            margin-bottom: 5px;
            color: #4361ee;
        }

        .user-details p {
            margin: 5px 0;
            color: #777;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .approve-btn {
            background: #06d6a0;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .reject-btn {
            background: #e63946;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #ecffecec;
            color: #06d6a0;
            border: 1px solid #06d6a0;
        }

        .error {
            background: #ffecec;
            color: #e63946;
            border: 1px solid #e63946;
        }

        .no-pending {
            text-align: center;
            padding: 40px;
            color: #777;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <?php include 'sidebar.php'; ?>

        <div class="content">
            <div class="admin-container">
                <div class="page-header">
                    <h1>User Registration Approvals</h1>
                    <p>Review and approve pending registration requests</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($pending_users)): ?>
                    <div class="no-pending">
                        <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <h3>No pending registration requests</h3>
                        <p>All registration requests have been processed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_users as $user): ?>
                        <div class="user-card">
                            <div class="user-info">
                                <div class="user-details">
                                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
                                    <p><strong>Requested:</strong> <?php echo date('M j, Y g:i A', strtotime($user['requested_at'])); ?></p>
                                    <?php if (!empty($user['phone_number'])): ?>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="action-buttons">
                                    <a href="admin_approval.php?action=approve&id=<?php echo $user['id']; ?>" class="approve-btn">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="admin_approval.php?action=reject&id=<?php echo $user['id']; ?>" class="reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
