<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// [IDOR-Medium: broken access control]
// applications.php (the listing page) only ever links here with the
// current candidate's own application IDs, so this endpoint was written
// assuming it would only ever be hit that way — it checks that *someone*
// is logged in, but never re-checks that the application being requested
// actually belongs to them. Every other place in the app that touches
// `applications` (employer candidate list, this candidate's own list
// query above) filters by owner id; this is the one that was missed.
$me = require_login();

$appId = (int)($_GET['application_id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT cv_path FROM applications WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $appId);
mysqli_stmt_execute($stmt);
$app = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$app || !$app['cv_path']) {
    http_response_code(404);
    die('Không tìm thấy CV.');
}

$path = __DIR__ . '/../../' . $app['cv_path'];
if (!is_file($path)) {
    http_response_code(404);
    die('File không tồn tại.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: inline; filename="' . basename($path) . '"');
readfile($path);
