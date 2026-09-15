<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$file = $_GET['file'] ?? 'cv_template_backend.txt';

// [Path Traversal - Medium]
// A report about arbitrary file reads via "../" in this parameter got a
// same-day fix: strip "../" out of the value before using it. str_replace()
// only makes a single pass over the string and doesn't re-scan what's
// left behind, so an overlapping sequence like "....//" has its middle
// "../" removed and the two halves that remain — "..", then "/" — simply
// close back up into "../" again.
$file = str_replace('../', '', $file);
$path = __DIR__ . '/templates/' . $file;

if (!is_file($path)) {
    http_response_code(404);
    die('Không tìm thấy file mẫu.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
readfile($path);
