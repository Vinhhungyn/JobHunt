<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json');

// Partner SSO passthrough: a recruiter-platform partner signs a token
// with their side of an agreed RSA keypair and deep-links the candidate
// in with it. Real partner tokens use alg=RS256. See jwt_verify_flexible()
// in config/jwt.php for how alg=HS256 became a full auth bypass here.
$token = bearer_token() ?? ($_GET['token'] ?? null);
if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'token required']);
    exit;
}

$keys = jwt_keys();
$claims = jwt_verify_flexible($token, $keys['public']);

if (!$claims) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid token']);
    exit;
}

echo json_encode([
    'message' => 'SSO token accepted',
    'impersonated_as' => [
        'sub' => $claims['sub'] ?? null,
        'email' => $claims['email'] ?? null,
        'role' => $claims['role'] ?? null,
    ],
]);
