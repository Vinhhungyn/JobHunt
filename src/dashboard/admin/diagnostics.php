<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('admin');
$pageTitle = 'Công cụ chẩn đoán';
$out = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '';

    // [Command Injection - Hard]
    // Written after the ping-tool finding on the employer side
    // (webhook_test.php, bypassed with a bare newline) — this blacklist
    // was extended to also cover `;`, `|`, `&`, and both newline
    // variants. It still doesn't cover backticks or `$()` command
    // substitution, which `sh -c` expands just as readily inside what
    // looks like a single argument to `nslookup`.
    if (preg_match('/[;|&\r\n]/', $host)) {
        $out = 'Rejected: invalid characters in host.';
    } else {
        $out = shell_exec('nslookup ' . $host . ' 2>&1');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/admin/index.php">Tổng quan</a>
      <a href="/dashboard/admin/users.php">Người dùng</a>
      <a href="/dashboard/admin/approve_jobs.php">Duyệt tin</a>
      <a href="/dashboard/admin/logs.php">Nhật ký hệ thống</a>
      <a href="/dashboard/admin/diagnostics.php" class="active">Công cụ chẩn đoán</a>
    </div>
    <div class="card">
      <h2>Tra cứu DNS</h2>
      <p style="color:var(--ink-soft);font-size:14px;">Công cụ nội bộ để admin kiểm tra domain của employer trước khi duyệt tin.</p>
      <form class="stacked" method="post" action="/dashboard/admin/diagnostics.php">
        <label>Hostname</label>
        <input type="text" name="host" placeholder="techcorp.example">
        <button type="submit" class="btn-outline" style="margin-top:14px;">Tra cứu</button>
      </form>
      <?php if ($out !== null): ?><pre class="alert alert-ok" style="white-space:pre-wrap;"><?= safe($out) ?></pre><?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
