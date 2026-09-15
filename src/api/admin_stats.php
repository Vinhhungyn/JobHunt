<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json');

$secret = getenv('JWT_SECRET') ?: 'jobhunt123';
$token = bearer_token();
$claims = $token ? jwt_verify_hs256($token, $secret) : null;

if (!$claims) {
    http_response_code(401);
    echo json_encode(['error' => 'missing or invalid token']);
    exit;
}

// The role check itself is correct — the problem is upstream: nothing
// stops an attacker who has recovered JWT_SECRET from minting a token
// with "role":"admin" in the first place. [see JWT-Medium in README]
if (($claims['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'admin role required']);
    exit;
}

$users = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) c FROM users'))['c'];
$emails = mysqli_fetch_all(mysqli_query($conn, 'SELECT id, email, role FROM users'), MYSQLI_ASSOC);

echo json_encode(['total_users' => (int)$users, 'users' => $emails]);
