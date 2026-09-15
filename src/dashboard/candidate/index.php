<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('candidate');
$pageTitle = 'Hồ sơ của tôi';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- candidate_profiles fields: normal, scoped, prepared statement ---
    $headline = trim($_POST['headline'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);

    $stmt = mysqli_prepare($conn, 'UPDATE candidate_profiles SET headline=?, bio=?, skills=?, experience_years=? WHERE user_id=?');
    mysqli_stmt_bind_param($stmt, 'sssii', $headline, $bio, $skills, $experience_years, $me['id']);
    mysqli_stmt_execute($stmt);

    // --- users fields: generalized "editable profile fields" updater ---
    // [IDOR-Hard: mass assignment]
    // Built generically so new profile fields (added a lot lately — phone,
    // then a "public" toggle, then this) don't each need a hand-written
    // UPDATE. The list below was meant to hold only user-editable, non-
    // sensitive columns; role/is_verified were added in the same sprint as
    // an "account type" self-service feature that was cut before launch,
    // but the fields were never removed from this list.
    $allowedUserFields = ['full_name', 'phone', 'role', 'is_verified'];
    $setParts = [];
    $params = [];
    $types = '';
    foreach ($allowedUserFields as $f) {
        if (isset($_POST[$f])) {
            $setParts[] = "$f = ?";
            $params[] = $_POST[$f];
            $types .= 's';
        }
    }
    if ($setParts) {
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $types .= 'i';
        $params[] = $me['id'];
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
    }

    flash('ok', 'Cập nhật hồ sơ thành công.');
    redirect('/dashboard/candidate/index.php');
}

$stmt = mysqli_prepare($conn, 'SELECT u.full_name, u.phone, u.email, u.role, cp.headline, cp.bio, cp.skills, cp.experience_years, cp.resume_path
    FROM users u JOIN candidate_profiles cp ON cp.user_id = u.id WHERE u.id = ?');
mysqli_stmt_bind_param($stmt, 'i', $me['id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/candidate/index.php" class="active">Hồ sơ</a>
      <a href="/dashboard/candidate/applications.php">Đơn ứng tuyển</a>
      <a href="/dashboard/candidate/recommendations.php">Gợi ý việc làm</a>
      <a href="/messages.php">Tin nhắn</a>
    </div>
    <div class="card">
      <h2>Hồ sơ ứng viên</h2>
      <p style="color:var(--ink-soft);font-size:14px;">Vai trò hiện tại: <strong><?= safe($profile['role']) ?></strong></p>
      <form class="stacked" method="post" action="/dashboard/candidate/index.php">
        <label>Họ tên</label>
        <input type="text" name="full_name" value="<?= safe($profile['full_name']) ?>">
        <label>Điện thoại</label>
        <input type="text" name="phone" value="<?= safe($profile['phone'] ?? '') ?>">
        <label>Chức danh mong muốn</label>
        <input type="text" name="headline" value="<?= safe($profile['headline'] ?? '') ?>">
        <label>Giới thiệu bản thân</label>
        <textarea name="bio" rows="4"><?= safe($profile['bio'] ?? '') ?></textarea>
        <label>Kỹ năng (phân cách bởi dấu phẩy)</label>
        <input type="text" name="skills" value="<?= safe($profile['skills'] ?? '') ?>">
        <label>Số năm kinh nghiệm</label>
        <input type="number" name="experience_years" value="<?= (int)$profile['experience_years'] ?>" min="0" max="40">
        <button type="submit" class="btn-primary" style="margin-top:20px;">Lưu thay đổi</button>
      </form>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
