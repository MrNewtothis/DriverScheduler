<?php
session_start();
require_once "connection/db.php";

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/main.php');
        exit;
    } else {
        header('Location: user/main.php');
        exit;
    }
}

$register_success = false;
$register_error = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration
    if (isset($_POST['register_name'])) {
        $name = trim($_POST['register_name']);
        $unit = trim($_POST['register_unit']);
        $birthdate = $_POST['register_birthdate'];
        $age = intval($_POST['register_age']);
        $phone = trim($_POST['register_phone']);
        $email = trim($_POST['register_email']);
        $username = trim($email); // Use email as username
        $password = $_POST['register_password'];
        $confirm = $_POST['register_confirm'];

        // Validation
        if ($password !== $confirm) {
            $register_error = 'Passwords do not match!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'Invalid email address!';
        } elseif ($age < 18 || $age > 100) {
            $register_error = 'Age must be between 18 and 100!';
        } else {
            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $register_error = 'Email already registered!';
            } else {
                // Insert user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Default role to 'user' if not specified
                $role = 'user';
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, unit, birthdate, age, phone, email, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $name, $unit, $birthdate, $age, $phone, $email, $role]);
                $register_success = true;
            }
        }
    }
    // Login
    if (isset($_POST['login_username'])) {
        $username = trim($_POST['login_username']);
        $password = $_POST['login_password'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['name'] = $user['name']; // Ensure compatibility with forms expecting $_SESSION['name']
            $_SESSION['user_role'] = isset($user['role']) ? $user['role'] : 'user';
            if (isset($user['role']) && $user['role'] === 'admin') {
                header('Location: admin/main.php');
            } else {
                header('Location: user/main.php');
            }
            exit;
        } else {
            $login_error = 'Invalid username/email or password!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIA Equipment Unit System - Login/Register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imgs/nialogo.png">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            background: #f4f8fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2.5rem 2rem 2rem 2rem;
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .auth-logo {
            width: 64px;
            margin-bottom: 1rem;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a3c6b;
            margin-bottom: 1.5rem;
        }
        .auth-toggle {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .auth-toggle button {
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #1a3c6b;
            padding: 0.5rem 1rem;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: border 0.2s, color 0.2s;
        }
        .auth-toggle button.active {
            border-bottom: 2px solid #1a3c6b;
            color: #0d2446;
        }
        .auth-form {
            width: 100%;
            display: none;
            flex-direction: column;
            gap: 1.2rem;
            box-sizing: border-box;
        }
        .auth-form.active {
            display: flex;
        }
        .auth-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d9e6;
            border-radius: 8px;
            font-size: 1rem;
            background: #f7fafd;
            margin: 0;
        }
        .auth-form button[type="submit"] {
            background: #1a3c6b;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s;
        }
        .auth-form button[type="submit"]:hover {
            background: #0d2446;
        }
        .back-btn {
            margin-top: 1.5rem;
            color: #1a3c6b;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        .back-btn:hover {
            color: #0d2446;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <img src="imgs/nialogo.png" alt="NIA Logo" class="auth-logo">
        <div class="auth-title">NIA Equipment Unit System</div>
        <div class="auth-toggle">
            <button id="loginTab" class="active" onclick="showForm('login')">Login</button>
            <button id="registerTab" onclick="showForm('register')">Register</button>
        </div>
        <?php if ($register_success): ?>
            <div style="color: #22c55e; font-weight: 600; margin-bottom: 1rem;">Registration successful! You can now log in.</div>
        <?php elseif ($register_error): ?>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 1rem;">Error: <?= htmlspecialchars($register_error) ?></div>
        <?php endif; ?>
        <?php if ($login_error): ?>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 1rem;">Error: <?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form id="loginForm" class="auth-form active" method="post" action="">
            <input type="text" name="login_username" placeholder="Username or Email" required>
            <input type="password" name="login_password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <form id="registerForm" class="auth-form" method="post" action="">
            <input type="text" name="register_name" placeholder="Full Name" required>
            <input type="text" name="register_unit" placeholder="Unit" required>
            <input type="date" name="register_birthdate" placeholder="Birthdate" required>
            <input type="number" name="register_age" placeholder="Age" min="18" max="100" required>
            <input type="text" name="register_phone" placeholder="Phone Number" required>
            <input type="email" name="register_email" placeholder="Email" required>
            <input type="password" name="register_password" placeholder="Password" required>
            <input type="password" name="register_confirm" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
    </div>
    <script>
        function showForm(form) {
            document.getElementById('loginForm').classList.remove('active');
            document.getElementById('registerForm').classList.remove('active');
            document.getElementById('loginTab').classList.remove('active');
            document.getElementById('registerTab').classList.remove('active');
            if(form === 'login') {
                document.getElementById('loginForm').classList.add('active');
                document.getElementById('loginTab').classList.add('active');
            } else {
                document.getElementById('registerForm').classList.add('active');
                document.getElementById('registerTab').classList.add('active');
            }
        }
    </script>
</body>
</html>
