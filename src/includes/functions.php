<?php
/**
 * Small helpers shared across pages.
 */

/**
 * "Fast" output escaper the team introduced instead of htmlspecialchars()
 * for anything rendered a lot (search results, listings) — it strips the
 * two characters needed to open a new tag, so a plain XSS payload like
 * <script>alert(1)</script> is neutered. It does NOT touch quote
 * characters, so it's only safe for text nodes, never for values dropped
 * inside an HTML attribute. See index.php search box for where that
 * distinction gets missed (reflected XSS lab).
 */
function e(string $s): string
{
    return str_replace(['<', '>'], ['&lt;', '&gt;'], $s);
}

/** Properly-escaped output for attribute contexts — used inconsistently. */
function safe(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $msg = null)
{
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function format_salary(int $min, int $max): string
{
    if ($min <= 0 && $max <= 0) {
        return 'Thoả thuận';
    }
    return number_format($min) . ' - ' . number_format($max) . ' VNĐ';
}

/**
 * CSP rollout, phase 1: the team added this after a pentest finding about
 * "missing security headers" flagged in an earlier report, but kept
 * 'unsafe-inline' because half the templates still have inline <script>
 * blocks (see job.php) that would break otherwise. Ticket to remove it
 * and externalize those scripts is JOBHUNT-482, still open.
 */
function send_csp_header(): void
{
    header("Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data:; " .
        "object-src 'none';");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}
