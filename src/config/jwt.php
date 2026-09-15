<?php
/**
 * Minimal JWT helpers for the mobile API. Two independent signing/
 * verification paths exist, matching two features that were built at
 * different times — see the comments on each vulnerable function for
 * how they went wrong. Not a general-purpose library; kept small and
 * explicit on purpose.
 */

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}

/** Loads (and lazily generates) the RSA keypair used for partner SSO tokens. */
function jwt_keys(): array
{
    $dir = __DIR__ . '/../.keys';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $privPath = $dir . '/jwt_private.pem';
    $pubPath = $dir . '/jwt_public.pem';
    if (!is_file($privPath) || !is_file($pubPath)) {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $priv);
        $pub = openssl_pkey_get_details($res)['key'];
        file_put_contents($privPath, $priv);
        file_put_contents($pubPath, $pub);
    }
    return ['private' => file_get_contents($privPath), 'public' => file_get_contents($pubPath)];
}

// ---------------------------------------------------------------------
// Path 1: normal login (api/auth.php -> api/jobs.php, api/admin_stats.php)
// ---------------------------------------------------------------------

function jwt_issue_hs256(array $payload, string $secret): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    $sig = hash_hmac('sha256', "$h.$p", $secret, true);
    return "$h.$p." . b64url_encode($sig);
}

/**
 * [JWT-Medium: weak secret]
 * JWT_SECRET (see .env.example / docker-compose.yml) is a short,
 * dictionary-guessable string picked when the mobile API was first
 * stood up as a proof of concept, and never rotated to something
 * generated for production use. Anyone who recovers it — a hashcat run
 * against a captured token's signature, or just finding it checked into
 * a leaked .env — can mint a token for any user id and role from
 * scratch, with no account or password needed at all.
 */
function jwt_verify_hs256(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$h, $p, $s] = $parts;
    $header = json_decode(b64url_decode($h), true);
    if (!$header || ($header['alg'] ?? '') !== 'HS256') {
        return null;
    }
    $expected = hash_hmac('sha256', "$h.$p", $secret, true);
    if (!hash_equals($expected, b64url_decode($s))) {
        return null;
    }
    $payload = json_decode(b64url_decode($p), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) {
        return null;
    }
    return $payload;
}

// ---------------------------------------------------------------------
// Path 2: partner SSO passthrough (api/sso_verify.php)
// ---------------------------------------------------------------------

function jwt_issue_rs256(array $payload, string $privateKeyPem): string
{
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    openssl_sign("$h.$p", $sig, $privateKeyPem, OPENSSL_ALGO_SHA256);
    return "$h.$p." . b64url_encode($sig);
}

/**
 * [JWT-Hard: algorithm confusion]
 * Added when a partner recruiter platform needed to hand us tokens they
 * signed themselves, so candidates could deep-link in already
 * authenticated. It was written by copy-pasting jwt_verify_hs256() above
 * and "making it handle whichever algorithm the partner sends" — the
 * RS256 branch is fine: it verifies against the partner's RSA *public*
 * key (api/jwks.php serves it, by design — it's meant to be public).
 * The HS256 branch reuses that same public key string as the HMAC
 * secret, because at the time it was written there was no separate
 * shared secret configured for this integration and the public key was
 * the only key material on hand. Since the public key is, by
 * definition, public, anyone can compute a valid HMAC signature over it
 * themselves — they just need to set `alg: HS256` in the header instead
 * of the `RS256` a real partner token would carry.
 */
function jwt_verify_flexible(string $token, string $rsaPublicKeyPem): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$h, $p, $s] = $parts;
    $header = json_decode(b64url_decode($h), true);
    $sig = b64url_decode($s);
    if (!$header) {
        return null;
    }
    $alg = $header['alg'] ?? '';
    if ($alg === 'RS256') {
        $ok = openssl_verify("$h.$p", $sig, $rsaPublicKeyPem, OPENSSL_ALGO_SHA256) === 1;
    } elseif ($alg === 'HS256') {
        $expected = hash_hmac('sha256', "$h.$p", $rsaPublicKeyPem, true);
        $ok = hash_equals($expected, $sig);
    } else {
        return null;
    }
    if (!$ok) {
        return null;
    }
    $payload = json_decode(b64url_decode($p), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) {
        return null;
    }
    return $payload;
}

function bearer_token(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth === '' && function_exists('apache_request_headers')) {
        $auth = apache_request_headers()['Authorization'] ?? '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return trim($m[1]);
    }
    return null;
}
