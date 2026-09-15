<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Đăng ký';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'candidate';
    if (!in_array($role, ['candidate', 'employer'], true)) {
        $role = 'candidate'; // admin is never selectable at signup
    }

    if ($fullName === '' || $email === '' || strlen($password) < 6) {
        $error = 'Vui lòng nhập đầy đủ thông tin (mật khẩu tối thiểu 6 ký tự).';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->fetch_assoc()) {
            $error = 'Email đã được sử dụng.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO users (email, password, role, full_name) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssss', $email, $hash, $role, $fullName);
            mysqli_stmt_execute($stmt);
            $userId = mysqli_insert_id($conn);

            if ($role === 'candidate') {
                $stmt = mysqli_prepare($conn, 'INSERT INTO candidate_profiles (user_id) VALUES (?)');
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
            } else {
                $companyName = $fullName . "'s Company";
                $stmt = mysqli_prepare($conn, 'INSERT INTO employer_profiles (user_id, company_name) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'is', $userId, $companyName);
                mysqli_stmt_execute($stmt);
            }

            flash('ok', 'Đăng ký thành công, mời bạn đăng nhập.');
            redirect('/login.php');
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container" style="max-width:460px;">
  <div class="card">
    <h2>Tạo tài khoản</h2>
    <?php if ($error): ?><div class="alert alert-err"><?= safe($error) ?></div><?php endif; ?>
    <form class="stacked" method="post" action="/register.php">
      <label>Họ tên</label>
      <input type="text" name="full_name" required value="<?= safe($_POST['full_name'] ?? '') ?>">
      <label>Email</label>
      <input type="email" name="email" required value="<?= safe($_POST['email'] ?? '') ?>">
      <label>Mật khẩu</label>
      <input type="password" name="password" required minlength="6">
      <label>Bạn là</label>
      <select name="role">
        <option value="candidate">Ứng viên</option>
        <option value="employer">Nhà tuyển dụng</option>
      </select>
      <button type="submit" class="btn-primary" style="margin-top:20px;width:100%;">Đăng ký</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
