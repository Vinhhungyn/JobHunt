<?php
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json');

// Public by design — this is the RSA public key partner platforms use to
// verify tokens *we* sign for them, and that our own SSO passthrough
// uses to verify tokens *they* sign for us (api/sso_verify.php).
// Publishing it is correct; the bug is what api/sso_verify.php does
// with it, not this endpoint. See JWT-Hard in README.
$keys = jwt_keys();
echo json_encode(['public_key_pem' => $keys['public']]);
