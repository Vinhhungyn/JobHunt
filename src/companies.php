<?php
$pageTitle = 'Công ty';
require_once __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$founded_after = trim($_GET['founded_after'] ?? ''); // dropdown: "" | 2000 | 2010 | 2015 | 2020

$sql = "SELECT e.user_id, e.company_name, e.company_desc, e.website, e.founded_year,
               (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.user_id AND j.status = 'approved') AS open_jobs
        FROM employer_profiles e WHERE 1=1";

if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $sql .= " AND e.company_name LIKE '%$qEsc%'";
}
// [SQLi-Hard: blind boolean]
// founded_after only ever comes from a <select> of fixed years in the UI
// (same assumption the search page makes about salary_min — "it's a
// controlled dropdown value, not free text"), so it's concatenated as-is.
// Unlike the job search page, this page never echoes query results or
// DB errors back — a mismatched query here just silently returns 0 or
// more company cards, which is exactly what makes it a *blind* injection
// point rather than a visible one: the only observable signal is
// true (companies shown) vs false (empty list).
if ($founded_after !== '') {
    $sql .= " AND e.founded_year >= " . $founded_after;
}

$result = mysqli_query($conn, $sql);
$companies = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $companies[] = $row;
    }
}
?>
<section class="section container">
  <div class="section-title"><h2>Công ty đang tuyển dụng</h2></div>
  <form method="get" action="/companies.php" class="search-box" style="grid-template-columns:2fr 1fr auto;margin-bottom:24px;">
    <input type="text" name="q" placeholder="Tên công ty..." value="<?= safe($q) ?>">
    <select name="founded_after">
      <option value="">Năm thành lập</option>
      <?php foreach ([2000, 2010, 2015, 2020] as $y): ?>
        <option value="<?= $y ?>" <?= $founded_after === (string)$y ? 'selected' : '' ?>>Từ <?= $y ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Lọc</button>
  </form>

  <?php if (!$companies): ?>
    <p>Không tìm thấy công ty phù hợp.</p>
  <?php else: ?>
    <div class="job-grid">
      <?php foreach ($companies as $c): ?>
        <div class="job-card">
          <h3><?= safe($c['company_name']) ?></h3>
          <p class="company"><?= safe(mb_strimwidth($c['company_desc'] ?? '', 0, 100, '...')) ?></p>
          <div class="meta">
            <span class="tag"><?= (int)$c['open_jobs'] ?> việc đang tuyển</span>
            <?php if ($c['founded_year']): ?><span class="tag orange">Thành lập <?= (int)$c['founded_year'] ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
