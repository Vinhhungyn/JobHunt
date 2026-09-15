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

$result = mysqli_query($conn, "SELECT j.id, j.title, j.location, j.salary_min, j.salary_max, e.company_name
    FROM jobs j JOIN employer_profiles e ON e.user_id = j.employer_id
    WHERE j.status = 'approved' ORDER BY j.created_at DESC LIMIT 50");
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(['user' => ['id' => $claims['sub'], 'role' => $claims['role']], 'jobs' => $jobs]);
