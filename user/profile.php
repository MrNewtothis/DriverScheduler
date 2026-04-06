<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require_once '../connection/db.php';
$user_id = $_SESSION['user_id'];

if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $unit = trim($_POST['unit']);
        $birthdate = $_POST['birthdate'];
        $age = intval($_POST['age']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        if (!$name || !$unit || !$birthdate || $age < 18 || $age > 100 || !$phone || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Please fill all fields correctly.']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email already in use.']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE users SET name=?, unit=?, birthdate=?, age=?, phone=?, email=? WHERE id=?');
        $stmt->execute([$name, $unit, $birthdate, $age, $phone, $email, $user_id]);
        $user = compact('name','unit','birthdate','age','phone','email');
        echo json_encode(['success' => true, 'user' => $user]);
        exit;
    } else {
        $stmt = $pdo->prepare('SELECT name, unit, birthdate, age, phone, email FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($user);
        exit;
    }
}

$success = false;
$error = '';

// Fetch user info
$stmt = $pdo->prepare('SELECT name, unit, birthdate, age, phone, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $birthdate = $_POST['birthdate'];
    $age = intval($_POST['age']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    // Basic validation
    if (!$name || !$unit || !$birthdate || $age < 18 || $age > 100 || !$phone || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please fill all fields correctly.';
    } else {
        // Check for duplicate email (except self)
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email already in use.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name=?, unit=?, birthdate=?, age=?, phone=?, email=? WHERE id=?');
            $stmt->execute([$name, $unit, $birthdate, $age, $phone, $email, $user_id]);
            $success = true;
            // Refresh user info
            $user = compact('name','unit','birthdate','age','phone','email');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="stylesheet" href="../css/usermain.css">
    <style>
    .profile-container {
        max-width: 420px;
        margin: 40px auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px 0 rgba(31,38,135,0.10);
        padding: 32px 24px 24px 24px;
    }
    .profile-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #1a3c6b;
        margin-bottom: 18px;
        text-align: center;
    }
    .profile-form label {
        font-weight: 600;
        color: #1a3c6b;
        margin-bottom: 4px;
        display: block;
    }
    .profile-form input[type="text"],
    .profile-form input[type="email"],
    .profile-form input[type="date"],
    .profile-form input[type="number"] {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 1px solid #d1d9e6;
        border-radius: 8px;
        font-size: 1rem;
        background: #f7fafd;
        margin-bottom: 1.2rem;
        box-sizing: border-box;
    }
    .profile-form button[type="submit"] {
        background: #1a3c6b;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.5rem;
        width: 100%;
        transition: background 0.2s;
    }
    .profile-form button[type="submit"]:hover {
        background: #0d2446;
    }
    .profile-success {
        color: #22c55e;
        font-weight: 600;
        margin-bottom: 1rem;
        text-align: center;
    }
    .profile-error {
        color: #ef4444;
        font-weight: 600;
        margin-bottom: 1rem;
        text-align: center;
    }
    </style>
</head>
<body>
<?php include 'main.php'; // Show the header ?>
<div class="profile-container">
    <div class="profile-title">My Profile</div>
    <?php if ($success): ?>
        <div class="profile-success">Profile updated successfully!</div>
    <?php elseif ($error): ?>
        <div class="profile-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="profile-form" method="post" action="">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        <label for="unit">Unit</label>
        <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($user['unit']) ?>" required>
        <label for="birthdate">Birthdate</label>
        <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($user['birthdate']) ?>" required>
        <label for="age">Age</label>
        <input type="number" id="age" name="age" value="<?= htmlspecialchars($user['age']) ?>" min="18" max="100" required>
        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        <button type="submit">Update Profile</button>
    </form>
</div>
</body>
</html>
