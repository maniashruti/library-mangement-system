<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';

// Handle existing session messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation logic
        if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role'])) {
            throw new Exception('All fields are required.');
        }

        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Role validation
        $allowed_roles = ['student', 'faculty', 'librarian'];
        if (!in_array($role, $allowed_roles)) {
            throw new Exception('Invalid role selected.');
        }

        // Database check
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid credentials.');
        }

        // Check approval status
        if (in_array($role, ['student', 'faculty']) && !$user['approved']) {
            throw new Exception('Your account is pending approval.');
        }

        // Session management
        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'fullname' => $user['fullname'],
            'last_login' => time()
        ];

        // Handle redirects
        $location = match($role) {
            'student' => 'student_dashboard.php',
            'faculty' => 'faculty_dashboard.php',
            'librarian' => 'librarian_dashboard.php'
        };
        header("Location: $location");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPSU Library - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a90e2;
            --secondary: #6c5ce7;
            --success: #00b894;
            --warning: #fdcb6e;
            --error: #d63031;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 1rem;
        }

        .auth-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header img {
            height: 80px;
            margin-bottom: 1.5rem;
        }

        .auth-header h2 {
            color: #2d3436;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .auth-header p {
            color: #636e72;
            font-size: 0.95rem;
        }

        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .role-card {
            background: white;
            border: 2px solid #dfe6e9;
            border-radius: 10px;
            padding: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .role-card:hover {
            border-color: var(--primary);
        }

        .role-card.active {
            border-color: var(--primary);
            background: #f8f9ff;
        }

        .role-card.active::after {
            content: "\f00c";
            font-family: "Font Awesome 5 Free";
            position: absolute;
            top: 8px;
            right: 8px;
            color: var(--primary);
            font-weight: 900;
            font-size: 0.9rem;
        }

        .role-card i {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 0.8rem;
            display: block;
        }

        .role-card h3 {
            font-size: 1rem;
            color: #2d3436;
            margin-bottom: 0.3rem;
        }

        .role-card p {
            font-size: 0.8rem;
            color: #636e72;
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #357abd;
        }

        .toggle-auth {
            text-align: center;
            margin-top: 1.5rem;
            color: #636e72;
        }

        .toggle-auth a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .error-message, .success-message {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .error-message {
            background: #ffe6e6;
            border-left: 4px solid var(--error);
            color: var(--error);
        }

        .success-message {
            background: #e6fff3;
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .password-reset {
            text-align: right;
            margin: 1rem 0;
        }

        .password-reset a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem;
            }
            .role-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="logo.png" alt="PPSU Logo">
            <h2>Library Portal Login</h2>
            <p>Select your role to continue</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="role-selector">
            <div class="role-card active" data-role="student" onclick="selectRole('student')">
                <i class="fas fa-user-graduate"></i>
                <h3>Student</h3>
                <p>Access course materials and resources</p>
            </div>
            <div class="role-card" data-role="faculty" onclick="selectRole('faculty')">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Faculty</h3>
                <p>Upload materials and manage courses</p>
            </div>
            <div class="role-card" data-role="librarian" onclick="selectRole('librarian')">
                <i class="fas fa-book-reader"></i>
                <h3>Librarian</h3>
                <p>Manage library resources and users</p>
            </div>
        </div>

        <form id="loginForm" method="POST">
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Email Address" required>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>

            <div class="password-reset">
                <a href="#reset-password">Forgot Password?</a>
            </div>

            <input type="hidden" name="role" id="selectedRole" value="student">
            <button type="submit" class="btn-primary">Login</button>
        </form>

        <div class="toggle-auth">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>

    <script>
        function selectRole(role) {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('active');
                if (card.dataset.role === role) card.classList.add('active');
            });
            document.getElementById('selectedRole').value = role;
        }

        // Initialize role selection
        selectRole('student');
    </script>
</body>
</html>