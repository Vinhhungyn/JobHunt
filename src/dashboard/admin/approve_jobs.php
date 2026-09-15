<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('admin');
$pageTitle = 'Duyệt tin tuyển dụng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = (int)($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approved', 'rejected'], true)) {
        $stmt = mysqli_prepare($conn, 'UPDATE jobs SET status = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $action, $jobId);
        mysqli_stmt_execute($stmt);
        $stmt = mysqli_prepare($conn, 'INSERT INTO admin_logs (action, detail, actor) VALUES (?, ?, ?)');
        $act = 'job_' . $action;
        $detail = "job_id=$jobId";
        mysqli_stmt_bind_param($stmt, 'sss', $act, $detail, $me['email']);
        mysqli_stmt_execute($stmt);
    }
    redirect('/dashboard/admin/approve_jobs.php');
}

$result = mysqli_query($conn, "SELECT j.id, j.title, j.status, j.created_at, e.company_name
    FROM jobs j JOIN employer_profiles e ON e.user_id = j.employer_id
    ORDER BY (j.status = 'pending') DESC, j.created_at DESC");
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/admin/index.php">Tổng quan</a>
      <a href="/dashboard/admin/users.php">Người dùng</a>
      <a href="/dashboard/admin/approve_jobs.php" class="active">Duyệt tin</a>
      <a href="/dashboard/admin/logs.php">Nhật ký hệ thống</a>
      <a href="/dashboard/admin/diagnostics.php">Công cụ chẩn đoán</a>
    </div>
    <div class="card">
      <h2>Tin tuyển dụng</h2>
      <table class="data">
        <thead><tr><th>Tiêu đề</th><th>Công ty</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j): ?>
          <tr>
            <td><a href="/job.php?id=<?= (int)$j['id'] ?>"><?= safe($j['title']) ?></a></td>
            <td><?= safe($j['company_name']) ?></td>
            <td><span class="badge <?= safe($j['status']) ?>"><?= safe($j['status']) ?></span></td>
            <td>
              <form method="post" action="/dashboard/admin/approve_jobs.php" style="display:inline;">
                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                <button type="submit" name="action" value="approved" class="btn-outline">Duyệt</button>
                <button type="submit" name="action" value="rejected" class="btn-outline">Từ chối</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
