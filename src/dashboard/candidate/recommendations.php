<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('candidate');
$pageTitle = 'Gợi ý việc làm';

// bio was written through a prepared statement in dashboard/candidate/index.php,
// so nothing malicious could have executed when it was *saved* — whatever
// the candidate typed is sitting in the database completely intact,
// injection payload and all.
$stmt = mysqli_prepare($conn, 'SELECT bio FROM candidate_profiles WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$bio = mysqli_stmt_get_result($stmt)->fetch_assoc()['bio'] ?? '';

$jobs = [];
if (trim($bio) !== '') {
    // [SQLi-Hard: second-order]
    // This is a *different* feature, written later, reusing the stored
    // bio to build a "jobs matching your profile" query. Whoever wrote
    // this only ever saw bio coming out of the database — it doesn't
    // "feel" like user input at this point, so it goes straight into the
    // query unescaped. The injection was already fully-formed when it
    // was stored; it just needed a second, less careful code path to
    // actually fire.
    $sql = "SELECT id, title, location, salary_min, salary_max FROM jobs
            WHERE status = 'approved' AND (description LIKE '%$bio%' OR title LIKE '%$bio%')
            LIMIT 10";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $jobs[] = $row;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/candidate/index.php">Hồ sơ</a>
      <a href="/dashboard/candidate/applications.php">Đơn ứng tuyển</a>
      <a href="/dashboard/candidate/recommendations.php" class="active">Gợi ý việc làm</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div class="card">
      <h2>Việc làm gợi ý dựa trên hồ sơ của bạn</h2>
      <p style="color:var(--ink-soft);font-size:14px;">Dựa trên phần "Giới thiệu bản thân" trong hồ sơ của bạn.</p>
      <?php if (!trim($bio)): ?>
        <p>Hãy cập nhật phần giới thiệu bản thân trong <a href="/dashboard/candidate/index.php">hồ sơ</a> để nhận gợi ý.</p>
      <?php elseif (!$jobs): ?>
        <p>Chưa tìm thấy việc làm phù hợp.</p>
      <?php else: ?>
        <div class="job-grid">
          <?php foreach ($jobs as $j): ?>
            <div class="job-card">
              <h3><a href="/job.php?id=<?= (int)$j['id'] ?>"><?= safe($j['title']) ?></a></h3>
              <div class="salary"><?= format_salary((int)$j['salary_min'], (int)$j['salary_max']) ?></div>
              <span class="tag"><?= safe($j['location']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
