<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'email and password are required']);
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id, email, password, role, full_name FROM users WHERE email = ?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid credentials']);
    exit;
}

$secret = getenv('JWT_SECRET') ?: 'jobhunt123';
$token = jwt_issue_hs256([
    'sub' => (int)$user['id'],
    'email' => $user['email'],
    'role' => $user['role'],
    'name' => $user['full_name'],
    'iat' => time(),
    'exp' => time() + 3600,
], $secret);

echo json_encode(['token' => $token, 'expires_in' => 3600]);
