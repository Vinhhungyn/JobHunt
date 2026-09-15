<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Webhook & công cụ';
$webhookResult = null;
$pingResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['webhook_url'])) {
    $url = trim($_POST['webhook_url']);
    $stmt = mysqli_prepare($conn, 'UPDATE employer_profiles SET webhook_url = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $url, $me['id']);
    mysqli_stmt_execute($stmt);

    // [SSRF-Medium]
    // A pentest last year flagged that this "test webhook" button could
    // be pointed at internal services, so a check was added for the
    // obvious cases attackers try first. It compares the *hostname
    // string* against a short blocklist rather than resolving it and
    // checking the actual IP range — so "0.0.0.0" (which every major OS
    // also routes to the local machine) sails straight through, along
    // with anything that isn't a literal, lowercase "localhost" or
    // "127.0.0.1".
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $blockedHosts = ['localhost', '127.0.0.1'];
    if (in_array($host, $blockedHosts, true)) {
        $webhookResult = 'Blocked: webhook URL points to a local address.';
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        $webhookResult = $body !== false
            ? 'Webhook reachable. Response (first 500 bytes): ' . substr($body, 0, 500)
            : 'Webhook request failed or timed out.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ping_host'])) {
    $host = $_POST['ping_host'];

    // [Command Injection - Medium]
    // Blocks the shell metacharacters everyone tries first (`;`, `|`,
    // `&`) after an earlier internal review flagged basic command
    // chaining here. A literal newline in the host field is just as
    // good a command separator once it reaches `sh -c`, though, and
    // isn't on the list.
    if (preg_match('/[;|&]/', $host)) {
        $pingResult = 'Rejected: invalid characters in host.';
    } else {
        $pingResult = shell_exec('ping -c 2 -W 2 ' . $host . ' 2>&1');
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
      <a href="/dashboard/employer/webhook_test.php" class="active">Webhook &amp; công cụ</a>
      <a href="/dashboard/employer/verify_doc.php">Xác thực doanh nghiệp</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div>
      <div class="card">
        <h2>Webhook thông báo ứng viên mới</h2>
        <p style="color:var(--ink-soft);font-size:14px;">JobHunt sẽ gửi POST request tới URL này mỗi khi có ứng viên mới.</p>
        <form class="stacked" method="post" action="/dashboard/employer/webhook_test.php">
          <label>Webhook URL</label>
          <input type="text" name="webhook_url" placeholder="https://hooks.example.com/...">
          <button type="submit" class="btn-outline" style="margin-top:14px;">Lưu &amp; kiểm tra kết nối</button>
        </form>
        <?php if ($webhookResult): ?><div class="alert alert-ok" style="word-break:break-all;"><?= safe($webhookResult) ?></div><?php endif; ?>
      </div>
      <div class="card">
        <h3>Kiểm tra kết nối máy chủ</h3>
        <p style="color:var(--ink-soft);font-size:14px;">Công cụ nội bộ để kiểm tra máy chủ webhook của bạn có phản hồi ping không.</p>
        <form class="stacked" method="post" action="/dashboard/employer/webhook_test.php">
          <label>Host / IP</label>
          <input type="text" name="ping_host" placeholder="hooks.example.com">
          <button type="submit" class="btn-outline" style="margin-top:14px;">Ping</button>
        </form>
        <?php if ($pingResult !== null): ?><pre class="alert alert-ok" style="white-space:pre-wrap;"><?= safe($pingResult) ?></pre><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
