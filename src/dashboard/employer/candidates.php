<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Ứng viên';

// Ownership IS enforced here (contrast with dashboard/candidate/cv_view.php,
// which forgets to): the join only returns applications for jobs this
// employer actually owns.
$stmt = mysqli_prepare($conn, "SELECT a.id, a.status, a.cv_path, a.created_at, j.title, u.full_name, u.email
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    JOIN users u ON u.id = a.candidate_id
    WHERE j.employer_id = ? ORDER BY a.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$apps = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = (int)($_POST['application_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
        $stmt = mysqli_prepare($conn, "UPDATE applications a JOIN jobs j ON j.id = a.job_id
            SET a.status = ? WHERE a.id = ? AND j.employer_id = ?");
        mysqli_stmt_bind_param($stmt, 'sii', $status, $appId, $me['id']);
        mysqli_stmt_execute($stmt);
    }
    redirect('/dashboard/employer/candidates.php');
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/employer/index.php">Tổng quan</a>
      <a href="/dashboard/employer/post_job.php">Đăng tin mới</a>
      <a href="/dashboard/employer/candidates.php" class="active">Ứng viên</a>
      <a href="/dashboard/employer/webhook_test.php">Webhook &amp; công cụ</a>
      <a href="/dashboard/employer/verify_doc.php">Xác thực doanh nghiệp</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div class="card">
      <h2>Ứng viên đã nộp hồ sơ</h2>
      <table class="data">
        <thead><tr><th>Ứng viên</th><th>Vị trí</th><th>Ngày nộp</th><th>Trạng thái</th><th>CV</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): ?>
          <tr>
            <td><?= safe($a['full_name']) ?><br><small style="color:var(--ink-soft);"><?= safe($a['email']) ?></small></td>
            <td><?= safe($a['title']) ?></td>
            <td><?= safe($a['created_at']) ?></td>
            <td>
              <form method="post" action="/dashboard/employer/candidates.php" style="display:inline;">
                <input type="hidden" name="application_id" value="<?= (int)$a['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><a href="/dashboard/employer/preview.php?doc=<?= rawurlencode(basename($a['cv_path']))?>">Xem CV</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$apps): ?><tr><td colspan="5">Chưa có ứng viên nào.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
