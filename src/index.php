<?php
$pageTitle = 'Tìm việc làm mơ ước';
require_once __DIR__ . '/includes/header.php';

$keyword = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');
$salary_min = trim($_GET['salary_min'] ?? ''); // dropdown of presets: "" | 0 | 10000000 | 20000000 | 30000000
$min_exp = trim($_GET['min_exp'] ?? '');        // dropdown of presets: "" | 0 | 1 | 2 | 3 | 5

$sql = "SELECT j.id, j.title, j.location, j.salary_min, j.salary_max, j.experience_required, j.created_at,
               e.company_name
        FROM jobs j
        JOIN employer_profiles e ON e.user_id = j.employer_id
        WHERE j.status = 'approved'";

// Keyword / location come from free-text inputs, so they get escaped and
// quoted like any other string input.
if ($keyword !== '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $sql .= " AND (j.title LIKE '%$kw%' OR j.description LIKE '%$kw%')";
}
if ($location !== '') {
    $loc = mysqli_real_escape_string($conn, $location);
    $sql .= " AND j.location LIKE '%$loc%'";
}

// --- salary_min / min_exp: these only ever come from a <select> of fixed
// numeric options in the UI, so the query below skips escaping/casting
// for them — "it's a number field, not free text". Nothing stops a
// request from setting these directly on the query string, though.
// [SQLi-Medium: UNION-based via salary_min] [SQLi-Hard: blind time-based via min_exp]
if ($salary_min !== '') {
    $sql .= " AND j.salary_min >= " . $salary_min;
}
if ($min_exp !== '') {
    // A prior pentest flagged SLEEP()-based blind SQLi on this field, so a
    // keyword filter was added. str_ireplace() runs a single pass, so a
    // payload that reforms the banned word after the first substitution
    // (e.g. "SLE" + "SLEEP" + "EP") slips through.
    $min_exp_clean = str_ireplace('sleep', '', $min_exp);
    $sql .= " AND j.experience_required >= " . $min_exp_clean;
}

$sql .= " ORDER BY j.created_at DESC LIMIT 30";

$result = mysqli_query($conn, $sql);
$jobs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
    }
}
?>
<section class="hero">
  <div class="container">
    <h1>Tìm việc làm phù hợp với bạn</h1>
    <p>Hàng ngàn tin tuyển dụng từ các công ty hàng đầu Việt Nam.</p>
    <form class="search-box" method="get" action="/index.php">
      <input type="text" name="q" placeholder="Vị trí, từ khoá..." value="<?= e($keyword) ?>">
      <select name="location">
        <option value="">Tất cả địa điểm</option>
        <?php foreach (['Ho Chi Minh City', 'Ha Noi', 'Da Nang', 'Remote'] as $loc): ?>
          <option value="<?= safe($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= safe($loc) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="salary_min">
        <option value="">Mức lương</option>
        <option value="0" <?= $salary_min === '0' ? 'selected' : '' ?>>Không giới hạn</option>
        <option value="10000000" <?= $salary_min === '10000000' ? 'selected' : '' ?>>Từ 10 triệu</option>
        <option value="20000000" <?= $salary_min === '20000000' ? 'selected' : '' ?>>Từ 20 triệu</option>
        <option value="30000000" <?= $salary_min === '30000000' ? 'selected' : '' ?>>Từ 30 triệu</option>
      </select>
      <button type="submit" class="btn-primary">Tìm kiếm</button>
    </form>
  </div>
</section>

<section class="section container">
  <div class="section-title">
    <h2><?= $keyword !== '' ? 'Kết quả cho "' . e($keyword) . '"' : 'Việc làm mới nhất' ?></h2>
    <span><?= count($jobs) ?> tin</span>
  </div>

  <?php if (!$jobs): ?>
    <p>Không tìm thấy tin tuyển dụng phù hợp.</p>
  <?php else: ?>
    <div class="job-grid">
      <?php foreach ($jobs as $job): ?>
        <div class="job-card">
          <h3><a href="/job.php?id=<?= (int)$job['id'] ?>"><?= safe($job['title']) ?></a></h3>
          <div class="company"><?= safe($job['company_name']) ?></div>
          <div class="salary"><?= format_salary((int)$job['salary_min'], (int)$job['salary_max']) ?></div>
          <div class="meta">
            <span class="tag"><?= safe($job['location']) ?></span>
            <span class="tag orange"><?= (int)$job['experience_required'] ?>+ năm KN</span>
          </div>
          <a href="/job.php?id=<?= (int)$job['id'] ?>" class="btn-outline">Xem chi tiết</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
