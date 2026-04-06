<?php
session_start();
require_once '../connection/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT name, unit, birthdate, age, phone, email, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo json_encode([
            'name' => $user['name'],
            'unit' => $user['unit'],
            'birthdate' => $user['birthdate'],
            'age' => $user['age'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tableHtml' => '<table style="width:100%;border-collapse:collapse;">'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;width:40%;">Full Name:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['name']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Unit:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['unit']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Birthdate:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['birthdate']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Age:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['age']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Phone:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['phone']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Email:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['email']).'</td></tr>'
                .'<tr><td style="font-weight:600;padding:8px 10px;background:#f8fafc;">Role:</td><td style="padding:8px 10px;">'.htmlspecialchars($user['role']).'</td></tr>'
                .'</table>'
        ]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
