<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$idRaw = $_GET['id'] ?? '1';

// Comment posting (stored XSS lives here — see below). Handled before
// header.php is included so require_login()/redirect() can still send
// headers if needed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $me = require_login();
    $jobId = (int)($_POST['job_id'] ?? 0);
    // strip_tags() with an allow-list was added after a bug report asked
    // for "basic formatting" (bold/italic/links) in comments. strip_tags()
    // only removes disallowed TAGS — it does not touch attributes on the
    // tags it keeps, so an allowed <a> can still carry an onmouseover/
    // onclick/style handler. [XSS-Medium: stored, via job comments]
    $clean = strip_tags($_POST['comment'] ?? '', '<b><i><u><a>');
    if (trim($clean) !== '') {
        $stmt = mysqli_prepare($conn, 'INSERT INTO job_comments (job_id, user_id, content) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iis', $jobId, $me['id'], $clean);
        mysqli_stmt_execute($stmt);
    }
    redirect('/job.php?id=' . $jobId);
}

// [SQLi-Medium: error-based]
// $id is escaped with mysqli_real_escape_string() "to be safe", but the
// column is numeric so the query never wraps it in quotes — escaping a
// value that is never quoted does nothing, because addslashes-style
// escaping only neutralizes quote characters, and there are none here to
// begin with. Anything past a plain integer flows straight into the
// query, letting error-based extraction functions like extractvalue()
// or updatexml() run and leak their result via the MySQL error message,
// which the app then prints directly.
$id = mysqli_real_escape_string($conn, $idRaw);
$sql = "SELECT j.*, e.company_name, e.company_desc, e.user_id AS employer_id
        FROM jobs j JOIN employer_profiles e ON e.user_id = j.employer_id
        WHERE j.id = $id AND j.status = 'approved'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    // "Just for local debugging" — APP_ENV is always development in this
    // stack, so this never actually gets gated off.
    if (getenv('APP_ENV') === 'development') {
        die('<pre>DB error: ' . htmlspecialchars(mysqli_error($conn)) . '</pre>');
    }
    die('Đã có lỗi xảy ra.');
}

$job = mysqli_fetch_assoc($result);
if (!$job) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container section"><p>Không tìm thấy tin tuyển dụng.</p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$commentsRes = mysqli_query($conn, 'SELECT c.content, c.created_at, u.full_name FROM job_comments c JOIN users u ON u.id = c.user_id WHERE c.job_id = ' . (int)$job['id'] . ' ORDER BY c.created_at DESC');
$me = current_user();
$pageTitle = $job['title'];
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
  <div class="card">
    <div id="ref-banner" style="margin-bottom:12px;"></div>
    <h1><?= safe($job['title']) ?></h1>
    <p class="company"><?= safe($job['company_name']) ?> · <?= safe($job['location']) ?></p>
    <div class="salary"><?= format_salary((int)$job['salary_min'], (int)$job['salary_max']) ?></div>
    <div class="meta">
      <span class="tag"><?= (int)$job['experience_required'] ?>+ năm kinh nghiệm</span>
    </div>
    <h3>Mô tả công việc</h3>
    <p><?= nl2br(safe($job['description'])) ?></p>
    <h3>Về công ty</h3>
    <p><?= nl2br(safe($job['company_desc'])) ?></p>

    <?php if ($me && $me['role'] === 'candidate'): ?>
      <a href="/apply.php?job_id=<?= (int)$job['id'] ?>" class="btn-primary">Ứng tuyển ngay</a>
    <?php elseif (!$me): ?>
      <a href="/login.php" class="btn-primary">Đăng nhập để ứng tuyển</a>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Bình luận / Đánh giá công ty</h3>
    <?php if ($me): ?>
      <form method="post" action="/job.php?id=<?= (int)$job['id'] ?>" class="stacked">
        <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
        <textarea name="comment" rows="3" placeholder="Chia sẻ trải nghiệm của bạn... (hỗ trợ <b>, <i>, <a>)"></textarea>
        <button type="submit" name="comment" value="1" class="btn-outline" style="margin-top:10px;">Gửi bình luận</button>
      </form>
    <?php endif; ?>
    <?php while ($c = mysqli_fetch_assoc($commentsRes)): ?>
      <div class="comment">
        <span class="who"><?= safe($c['full_name']) ?></span>
        <span class="when"><?= safe($c['created_at']) ?></span>
        <!-- content is stored pre-sanitized by strip_tags() at write time,
             so it is intentionally NOT escaped again here — re-escaping
             would show raw <b>/<i>/<a> tags to the user instead of
             rendering them. -->
        <p><?= $c['content'] ?></p>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<script>
// "Shared by a friend" banner — ?ref=Name is set when a job link is
// shared via the /share feature. [XSS-Hard: DOM-based]
// Uses innerHTML instead of textContent because the banner needs the
// bold <strong> around the name; nobody expected `ref` itself to carry
// markup since it's "just a name".
(function () {
  const params = new URLSearchParams(window.location.search);
  const ref = params.get('ref');
  if (ref) {
    document.getElementById('ref-banner').innerHTML =
      '<div class="alert alert-ok"><strong>' + ref + '</strong> đã chia sẻ tin này với bạn</div>';
  }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
