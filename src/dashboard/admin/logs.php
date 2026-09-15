<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('admin');
$pageTitle = 'Nhật ký hệ thống';

$result = mysqli_query($conn, 'SELECT action, detail, actor, created_at FROM admin_logs ORDER BY created_at DESC LIMIT 200');
$logs = mysqli_fetch_all($result, MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/admin/index.php">Tổng quan</a>
      <a href="/dashboard/admin/users.php">Người dùng</a>
      <a href="/dashboard/admin/approve_jobs.php">Duyệt tin</a>
      <a href="/dashboard/admin/logs.php" class="active">Nhật ký hệ thống</a>
      <a href="/dashboard/admin/diagnostics.php">Công cụ chẩn đoán</a>
    </div>
    <div class="card">
      <h2>Nhật ký hoạt động quản trị</h2>
      <table class="data">
        <thead><tr><th>Thời gian</th><th>Hành động</th><th>Chi tiết</th><th>Người thực hiện</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><?= safe($l['created_at']) ?></td>
            <td><?= safe($l['action']) ?></td>
            <td><?= safe($l['detail']) ?></td>
            <td><?= safe($l['actor']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="4">Chưa có nhật ký nào.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
