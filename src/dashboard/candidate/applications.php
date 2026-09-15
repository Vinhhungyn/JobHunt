<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('candidate');
$pageTitle = 'Đơn ứng tuyển';

$stmt = mysqli_prepare($conn, 'SELECT a.id, a.status, a.created_at, j.title, e.company_name
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    JOIN employer_profiles e ON e.user_id = j.employer_id
    WHERE a.candidate_id = ? ORDER BY a.created_at DESC');
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$apps = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/candidate/index.php">Hồ sơ</a>
      <a href="/dashboard/candidate/applications.php" class="active">Đơn ứng tuyển</a>
      <a href="/dashboard/candidate/recommendations.php">Gợi ý việc làm</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div class="card">
      <h2>Đơn ứng tuyển của tôi</h2>
      <table class="data">
        <thead><tr><th>Vị trí</th><th>Công ty</th><th>Ngày nộp</th><th>Trạng thái</th><th>CV</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): ?>
          <tr>
            <td><?= safe($a['title']) ?></td>
            <td><?= safe($a['company_name']) ?></td>
            <td><?= safe($a['created_at']) ?></td>
            <td><span class="badge <?= safe($a['status']) ?>"><?= safe($a['status']) ?></span></td>
            <td><a href="/dashboard/candidate/cv_view.php?application_id=<?= (int)$a['id'] ?>">Xem CV</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$apps): ?><tr><td colspan="5">Bạn chưa ứng tuyển vị trí nào.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
