<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Dashboard</title>
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
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        header {
            background: #4e54c8;
            color: white;
            padding: 25px;
            text-align: center;
        }

        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .dashboard-content {
            display: flex;
            flex-direction: column;
            padding: 30px;
        }

        .role-selection {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }

        .role-btn {
            flex: 1;
            min-width: 250px;
            background: white;
            border: none;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .role-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .role-btn.student {
            border-top: 5px solid #ff7e5f;
        }

        .role-btn.instructor {
            border-top: 5px solid #4cb8c4;
        }

        .role-btn.admin {
            border-top: 5px solid #3ca55c;
        }

        .role-btn i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .role-btn h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .role-btn p {
            color: #666;
            margin-bottom: 15px;
        }

        .role-btn .btn {
            background: #4e54c8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .role-btn.student .btn {
            background: #ff7e5f;
        }

        .role-btn.instructor .btn {
            background: #4cb8c4;
        }

        .role-btn.admin .btn {
            background: #3ca55c;
        }

        .role-btn .btn:hover {
            opacity: 0.9;
        }

        .dashboard-section {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .dashboard-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .dashboard-section h2 {
            color: #4e54c8;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .dashboard-section p {
            line-height: 1.6;
            color: #555;
            margin-bottom: 15px;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .role-selection {
                flex-direction: column;
            }

            .role-btn {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Educational Portal</h1>
            <p>Select your role to access the appropriate dashboard</p>
        </header>

        <div class="dashboard-content">
            <div class="role-selection" id="roleSelection">

                <div class="role-btn student">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Student</h3>
                    <p>Register as a new student or login to access your account</p>
                    <a class="btn" href="student/register.php">Student Registration</a>
                </div>

                <div class="role-btn instructor">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Instructor</h3>
                    <p>Manage courses, assignments, and student progress</p>
                    <a class="btn" href="instructor/login.php">Instructor Login</a>
                </div>

                <div class="role-btn admin">
                    <i class="fas fa-user-shield"></i>
                    <h3>Administrator</h3>
                    <p>System management and administrative functions</p>
                    <a class="btn" href="admin/dashboard.php">Admin Dashboard</a>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="js/main.js"></script>

</body>
</html>
