<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('admin');
$pageTitle = 'Bảng điều khiển quản trị';

$stats = [];
$stats['users'] = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) c FROM users'))['c'];
$stats['jobs_pending'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM jobs WHERE status='pending'"))['c'];
$stats['jobs_approved'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM jobs WHERE status='approved'"))['c'];
$stats['applications'] = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) c FROM applications'))['c'];

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/admin/index.php" class="active">Tổng quan</a>
      <a href="/dashboard/admin/users.php">Người dùng</a>
      <a href="/dashboard/admin/approve_jobs.php">Duyệt tin</a>
      <a href="/dashboard/admin/logs.php">Nhật ký hệ thống</a>
      <a href="/dashboard/admin/diagnostics.php">Công cụ chẩn đoán</a>
    </div>
    <div class="card">
      <h2>Tổng quan hệ thống</h2>
      <div class="job-grid">
        <div class="job-card"><h3><?= (int)$stats['users'] ?></h3><p class="company">Người dùng</p></div>
        <div class="job-card"><h3><?= (int)$stats['jobs_pending'] ?></h3><p class="company">Tin chờ duyệt</p></div>
        <div class="job-card"><h3><?= (int)$stats['jobs_approved'] ?></h3><p class="company">Tin đã duyệt</p></div>
        <div class="job-card"><h3><?= (int)$stats['applications'] ?></h3><p class="company">Đơn ứng tuyển</p></div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
