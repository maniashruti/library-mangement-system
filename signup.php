<?php
require_once 'config.php'; // Sessions started in config.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle existing session messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];

    // Password validation
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: signup.php");
        exit();
    }
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Initialize fields
    $security_question = '';
    $security_answer = '';
    $department = '';
    $position = '';

    // Faculty-specific fields
    if ($role === 'faculty') {
        if (empty($_POST['security_question'])) {
            $_SESSION['error'] = "Security question required!";
            header("Location: signup.php");
            exit();
        }
        $security_question = $_POST['security_question'];
        $security_answer = password_hash($_POST['security_answer'], PASSWORD_DEFAULT);
        $department = $_POST['department'] ?? '';
        $position = $_POST['position'] ?? '';
    }

    $approved = (in_array($role, ['faculty', 'student'])) ? 0 : 1;

    try {
        $pdo->beginTransaction();

        // Check existing email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email already exists!";
            header("Location: signup.php");
            exit();
        }

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users 
            (fullname, email, password, role, security_question, security_answer, department, position, approved)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $fullname,
            $email,
            $password,
            $role,
            $security_question,
            $security_answer,
            $department,
            $position,
            $approved
        ]);

        // Insert into pending_approvals for faculty/student
        if (in_array($role, ['faculty', 'student'])) {
            $stmt = $pdo->prepare("INSERT INTO pending_approvals (user_id) VALUES (?)");
            $stmt->execute([$pdo->lastInsertId()]);
        }

        $pdo->commit();

        $_SESSION['success'] = "Signup successful!" . (in_array($role, ['faculty', 'student']) ? " Awaiting approval." : "");
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: signup.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPSU Library - Sign Up</title>
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

        .faculty-note {
            background: #fff9e6;
            border-left: 4px solid var(--warning);
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            display: none;
            align-items: center;
            gap: 0.8rem;
            color: #2d3436;
        }

        .faculty-note i {
            color: var(--warning);
            font-size: 1.2rem;
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

        .security-questions {
            display: none;
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
        <h2>Create New Account</h2>
        <p>Select your role to get started</p>
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

    <div class="faculty-note" id="facultyNote">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Verification Required:</strong> Student and Faculty accounts need admin approval.
        </div>
    </div>

    <form id="signupForm" method="POST">
        <div class="form-group">
            <input type="text" class="form-control" name="fullname" placeholder="Full Name" required>
        </div>
        <div class="form-group">
            <input type="email" class="form-control" name="email" placeholder="Email Address" required>
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        
        <div class="security-questions" id="securityQuestions">
            <div class="form-group">
                <input type="text" class="form-control" name="department" placeholder="Department">
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="position" placeholder="Position">
            </div>
            <div class="form-group">
                <select class="form-control" name="security_question" required>
                    <option value="">Select Security Question</option>
                    <option>What was your first pet's name?</option>
                    <option>What city were you born in?</option>
                    <option>What is your mother's maiden name?</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="security_answer" placeholder="Security Answer" required>
            </div>
        </div>
        
        <input type="hidden" name="role" id="selectedRole" value="student">
        <button type="submit" class="btn-primary">Create Account</button>
    </form>

    <div class="toggle-auth">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<script>
    function selectRole(role) {
        document.querySelectorAll('.role-card').forEach(card => {
            card.classList.remove('active');
            if (card.dataset.role === role) card.classList.add('active');
        });
        
        document.getElementById('selectedRole').value = role;
        const needsApproval = (role === 'faculty' || role === 'student');
        document.getElementById('facultyNote').style.display = needsApproval ? 'flex' : 'none';
        
        const isFaculty = role === 'faculty';
        document.getElementById('securityQuestions').style.display = isFaculty ? 'block' : 'none';
        
        // Toggle required attributes for faculty fields
        const facultyFields = document.querySelectorAll('#securityQuestions [required]');
        facultyFields.forEach(field => field.required = isFaculty);
    }

    // Initialize role visibility
    selectRole('student');

    // Form validation
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            confirmPassword.focus();
        }
    });
</script>
</body>
</html>