<?php
session_start();
include('../includes/db.php'); // Your DB connection file

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();
        // Verify password
        if(password_verify($password, $user['password'])){
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // 'admin' or 'staff'

            // Redirect based on role
            if($_SESSION['role'] === 'admin'){
                header("Location: admin_dashboard.php");
            } else {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!-- Simple login form -->
<!DOCTYPE html>
<html>
<head>
    <title>Login - HR ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">HR ERP Login</h2>
        <?php if(isset($error)) echo "<p class='text-red-500 mb-4'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" class="w-full mb-4 p-2 border rounded" required>
            <input type="password" name="password" placeholder="Password" class="w-full mb-4 p-2 border rounded" required>
            <button type="submit" name="login" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
        </form>
    </div>
</body>
</html>
