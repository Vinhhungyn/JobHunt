<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Đăng tin tuyển dụng';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary_min = (int)($_POST['salary_min'] ?? 0);
    $salary_max = (int)($_POST['salary_max'] ?? 0);
    $experience_required = (int)($_POST['experience_required'] ?? 0);

    if ($title === '' || $description === '') {
        $error = 'Vui lòng nhập đầy đủ tiêu đề và mô tả.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO jobs (employer_id, title, description, location, salary_min, salary_max, experience_required, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, 'isssiii', $me['id'], $title, $description, $location, $salary_min, $salary_max, $experience_required);
        mysqli_stmt_execute($stmt);
        flash('ok', 'Đăng tin thành công! Tin của bạn đang chờ admin duyệt.');
        redirect('/dashboard/employer/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container" style="max-width:640px;">
  <div class="card">
    <h2>Đăng tin tuyển dụng mới</h2>
    <?php if ($error): ?><div class="alert alert-err"><?= safe($error) ?></div><?php endif; ?>
    <form class="stacked" method="post" action="/dashboard/employer/post_job.php">
      <label>Tiêu đề</label>
      <input type="text" name="title" required>
      <label>Mô tả công việc</label>
      <textarea name="description" rows="6" required></textarea>
      <label>Địa điểm</label>
      <input type="text" name="location" placeholder="Ho Chi Minh City">
      <label>Mức lương (VNĐ)</label>
      <div style="display:flex;gap:10px;">
        <input type="number" name="salary_min" placeholder="Tối thiểu">
        <input type="number" name="salary_max" placeholder="Tối đa">
      </div>
      <label>Số năm kinh nghiệm yêu cầu</label>
      <input type="number" name="experience_required" min="0" max="20">
      <button type="submit" class="btn-primary" style="margin-top:20px;">Đăng tin (chờ duyệt)</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
