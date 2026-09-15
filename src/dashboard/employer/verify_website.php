<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Xác thực website công ty';
$verifyResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['website_url'] ?? '');
    $host = (string)(parse_url($url, PHP_URL_HOST) ?? '');

    // [SSRF-Hard]
    // After JOBHUNT-190 (the webhook tester was bypassable with
    // "0.0.0.0", see webhook_test.php) this endpoint was written more
    // carefully: instead of string-matching a blocklist, it resolves the
    // real IP range via filter_var()'s built-in private/reserved-range
    // flags. The gap is that the range check only runs when filter_var()
    // recognizes the host as a *standard* dotted-quad or colon-hex IP to
    // begin with. A decimal (2130706433), octal (0177.0.0.1), or hex
    // (0x7f000001) form of 127.0.0.1 isn't "a valid IP" as far as that
    // function is concerned, so it's treated like any ordinary hostname
    // and the range check is skipped — while the resolver that actually
    // opens the outbound connection understands those numeric forms
    // just fine and connects straight to the loopback interface.
    $blocked = false;
    if ($host !== '' && filter_var($host, FILTER_VALIDATE_IP)) {
        $isPublic = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        $blocked = ($isPublic === false);
    }

    if ($blocked) {
        $verifyResult = 'Blocked: target resolves to a private/reserved IP range.';
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        $verifyResult = $body !== false
            ? 'Reachable. First 500 bytes of response: ' . substr($body, 0, 500)
            : 'Site unreachable or timed out.';
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/employer/index.php">Tổng quan</a>
      <a href="/dashboard/employer/post_job.php">Đăng tin mới</a>
      <a href="/dashboard/employer/candidates.php">Ứng viên</a>
      <a href="/dashboard/employer/webhook_test.php">Webhook &amp; công cụ</a>
      <a href="/dashboard/employer/verify_doc.php">Xác thực doanh nghiệp</a>
      <a href="/dashboard/employer/verify_website.php" class="active">Xác thực website</a>
    </div>
    <div class="card">
      <h2>Xác thực website công ty</h2>
      <p style="color:var(--ink-soft);font-size:14px;">Chúng tôi sẽ truy cập website của bạn để xác nhận công ty đang hoạt động.</p>
      <form class="stacked" method="post" action="/dashboard/employer/verify_website.php">
        <label>Website URL</label>
        <input type="text" name="website_url" placeholder="https://company.example.com">
        <button type="submit" class="btn-outline" style="margin-top:14px;">Xác thực</button>
      </form>
      <?php if ($verifyResult): ?><pre class="alert alert-ok" style="white-space:pre-wrap;word-break:break-all;"><?= safe($verifyResult) ?></pre><?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
