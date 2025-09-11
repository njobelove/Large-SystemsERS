<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $fullName = sanitizeInput($_POST['fullName'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $programId = (int)($_POST['program_id'] ?? 0);
    $levelId = (int)($_POST['level_id'] ?? 0);
    $dob = sanitizeInput($_POST['dob'] ?? '');
    $pob = sanitizeInput($_POST['pob'] ?? '');
    $school = sanitizeInput($_POST['school'] ?? '');

    // Validate required fields
    if (empty($fullName)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (empty($programId)) $errors[] = 'Program is required';
    if (empty($levelId)) $errors[] = 'Level is required';
    if (empty($dob)) $errors[] = 'Date of birth is required';
    if (empty($pob)) $errors[] = 'Place of birth is required';
    if (empty($school)) $errors[] = 'School name is required';

    // Validate program and level exist
    if ($programId > 0) {
        $programExists = getSingleRow("SELECT id FROM programs WHERE id = ?", [$programId], 'i');
        if (!$programExists) {
            $errors[] = 'Selected program is not valid';
        }
    }

    if ($levelId > 0) {
        $levelExists = getSingleRow("SELECT id FROM levels WHERE id = ?", [$levelId], 'i');
        if (!$levelExists) {
            $errors[] = 'Selected level is not valid';
        }
    }

    // Check if email already exists
    if (!empty($email)) {
        $existingUser = getSingleRow("SELECT id FROM users WHERE email = ?", [$email], 's');
        if ($existingUser) {
            $errors[] = 'Email already exists';
        }
    }

    if (empty($errors)) {
        // Handle file uploads
        $uploadDir = '../uploads/students/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $idCardPath = '';
        $gceCertPath = '';
        $birthCertPath = '';

        $files = ['idCard', 'gceCert', 'birthCert'];
        $filePaths = [&$idCardPath, &$gceCertPath, &$birthCertPath];

        foreach ($files as $index => $fileKey) {
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($_FILES[$fileKey]['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $filePath)) {
                    $filePaths[$index] = $filePath;
                } else {
                    $errors[] = "Failed to upload $fileKey";
                }
            }
        }

        if (empty($errors)) {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert student into database
            $sql = "INSERT INTO users (name, email, password, phone, program_id, level_id, date_of_birth, place_of_birth, last_school_attended, id_card_path, gce_cert_path, birth_cert_path, role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', NOW())";

            $result = executeNonQuery($sql, [
                $fullName, $email, $hashedPassword, $phone, $programId, $levelId,
                $dob, $pob, $school, $idCardPath, $gceCertPath, $birthCertPath
            ], 'ssssssssssss');

            if ($result) {
                $success = true;
                // Redirect to login page after successful registration
                header("Location: login.php");
                exit();
            } else {
                $errors[] = 'Failed to register student';
            }
        }
    }
}

// Get programs and levels for dropdowns
try {
    $programs = getMultipleRows("SELECT id, code, name FROM programs ORDER BY name", [], '');
} catch (Exception $e) {
    // Fallback if code column doesn't exist
    $programs = getMultipleRows("SELECT id, name FROM programs ORDER BY name", [], '');
    // Add empty code for each program
    foreach ($programs as &$program) {
        $program['code'] = '';
    }
}

try {
    $levels = getMultipleRows("SELECT id, code, name FROM levels ORDER BY name", [], '');
} catch (Exception $e) {
    // Fallback if code column doesn't exist
    $levels = getMultipleRows("SELECT id, name FROM levels ORDER BY name", [], '');
    // Add empty code for each level
    foreach ($levels as &$level) {
        $level['code'] = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }

        .header {
            background: #4a6fc4;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .header h2 {
            font-weight: 600;
            font-size: 28px;
        }

        .header p {
            margin-top: 8px;
            opacity: 0.9;
        }

        .form-container {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input:not([type="file"]) {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #4a6fc4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 196, 0.2);
        }

        .form-group select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group select:focus {
            border-color: #4a6fc4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 196, 0.2);
        }

        .file-input {
            grid-column: span 2;
        }

        .file-input input {
            margin-top: 6px;
        }

        .btn {
            background: #4a6fc4;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background: #3b5aa6;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .form-footer a {
            color: #4a6fc4;
            text-decoration: none;
        }

        .error-message {
            background: #f44336;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .file-input {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-user-graduate"></i> Student Registration</h2>
            <p>Create your student account to get started</p>
        </div>

        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Registration successful! Please check your email for login credentials.
                </div>
            <?php endif; ?>

            <form id="studentRegistrationForm" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" placeholder="Enter your full name"
                               value="<?php echo htmlspecialchars($_POST['fullName'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>

                    <div class="form-group">
                        <label for="program">Program</label>
                        <select id="program" name="program_id" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['id']; ?>"
                                        <?php echo (isset($_POST['program_id']) && $_POST['program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((!empty($program['code']) ? $program['code'] . ' - ' : '') . $program['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="level">Level</label>
                        <select id="level" name="level_id" required>
                            <option value="">Select Level</option>
                            <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>"
                                        <?php echo (isset($_POST['level_id']) && $_POST['level_id'] == $level['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($level['name'] . ' (' . $level['code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob"
                               value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pob">Place of Birth</label>
                        <input type="text" id="pob" name="pob" placeholder="Enter your place of birth"
                               value="<?php echo htmlspecialchars($_POST['pob'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="school">School Name</label>
                        <input type="text" id="school" name="school" placeholder="Enter your school name"
                               value="<?php echo htmlspecialchars($_POST['school'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group file-input">
                        <label for="idCard">ID Card</label>
                        <input type="file" id="idCard" name="idCard" accept="image/*,application/pdf" required>
                    </div>

                    <div class="form-group file-input">
                        <label for="gceCert">GCE Certificate</label>
                        <input type="file" id="gceCert" name="gceCert" accept="image/*,application/pdf" required>
                    </div>

                    <div class="form-group file-input">
                        <label for="birthCert">Birth Certificate</label>
                        <input type="file" id="birthCert" name="birthCert" accept="image/*,application/pdf" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Register Now
                </button>

                <div class="form-footer">
                    Already have an account? <a href="/acedemics2/student/login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
