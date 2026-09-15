<?php
/**
 * Shared mysqli connection. Most queries in this app use prepared
 * statements — the handful that don't are the intentional lab bugs and
 * are called out with a comment at the call site (see README.md for the
 * full map of where each vulnerability lives).
 */

$DB_HOST = getenv('DB_HOST') ?: 'mysql';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);
$DB_NAME = getenv('DB_NAME') ?: 'jobhunt';
$DB_USER = getenv('DB_USER') ?: 'jobhunt';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: 'jobhunt_dev_pw';

mysqli_report(MYSQLI_REPORT_OFF); // errors are surfaced manually so the
// error-based SQLi lab (job.php) actually has MySQL error text to leak.

$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);
if (!$conn) {
    http_response_code(500);
    // display_errors is On in this environment (see docker/php/php.ini) —
    // this intentionally leaks connection details, matching a real box
    // where debug mode never got turned off after launch.
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
