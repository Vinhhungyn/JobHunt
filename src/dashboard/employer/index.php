<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Bảng điều khiển nhà tuyển dụng';

$stmt = mysqli_prepare($conn, 'SELECT id, title, status, created_at,
    (SELECT COUNT(*) FROM applications a WHERE a.job_id = jobs.id) AS app_count
    FROM jobs WHERE employer_id = ? ORDER BY created_at DESC');
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, 'SELECT company_name, company_desc, website, webhook_url, founded_year FROM employer_profiles WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/employer/index.php" class="active">Tổng quan</a>
      <a href="/dashboard/employer/post_job.php">Đăng tin mới</a>
      <a href="/dashboard/employer/candidates.php">Ứng viên</a>
      <a href="/dashboard/employer/webhook_test.php">Webhook &amp; công cụ</a>
      <a href="/dashboard/employer/verify_doc.php">Xác thực doanh nghiệp</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div>
      <div class="card">
        <h2><?= safe($profile['company_name']) ?></h2>
        <p><?= safe($profile['company_desc'] ?? '') ?></p>
      </div>
      <div class="card">
        <h3>Tin đã đăng</h3>
        <table class="data">
          <thead><tr><th>Vị trí</th><th>Trạng thái</th><th>Ứng viên</th><th>Ngày đăng</th></tr></thead>
          <tbody>
          <?php foreach ($jobs as $j): ?>
            <tr>
              <td><?= safe($j['title']) ?></td>
              <td><span class="badge <?= safe($j['status']) ?>"><?= safe($j['status']) ?></span></td>
              <td><?= (int)$j['app_count'] ?></td>
              <td><?= safe($j['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$jobs): ?><tr><td colspan="4">Chưa có tin tuyển dụng nào.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
