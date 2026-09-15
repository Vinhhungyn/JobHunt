<?php
/**
 * Session bootstrap + auth helpers. Included by every page that needs
 * to know who's logged in.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,      // we're always behind TLS in this stack
        'httponly' => true,    // also set globally in php.ini, belt & suspenders
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => (int)$_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
    ];
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /login.php');
        exit;
    }
    return $u;
}

function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        http_response_code(403);
        die('Forbidden: this area requires the "' . htmlspecialchars($role) . '" role.');
    }
    return $u;
}

function login_user(array $userRow): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$userRow['id'];
    $_SESSION['email'] = $userRow['email'];
    $_SESSION['role'] = $userRow['role'];
    $_SESSION['full_name'] = $userRow['full_name'];
}
