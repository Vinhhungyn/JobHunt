<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Đăng nhập';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // [SQLi-Medium: login bypass]
    // Legacy compatibility shim: employer names imported years ago from an
    // old GBK-encoded HR system still show up mangled unless this
    // connection is switched to GBK before the login query runs
    // (ticket JOBHUNT-51, "never got around to migrating the encoding").
    // The team's understanding is that addslashes() is "basically the
    // same thing" as mysqli_real_escape_string() for preventing SQLi —
    // it isn't: addslashes() has no idea about connection charset, and
    // under GBK a crafted multi-byte prefix can absorb the backslash it
    // inserts, leaving the quote it was supposed to neutralize intact.
    mysqli_set_charset($conn, 'gbk');
    $email = addslashes($emailRaw);

    $sql = "SELECT id, email, password, role, full_name, is_banned
            FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && ($row = mysqli_fetch_assoc($result))) {
        if ((int)$row['is_banned'] === 1) {
            $error = 'Tài khoản đã bị khoá.';
        } elseif (password_verify($password, $row['password'])) {
            login_user($row);
            redirect('/dashboard/' . $row['role'] . '/index.php');
        } else {
            $error = 'Email hoặc mật khẩu không đúng.';
        }
    } else {
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container" style="max-width:420px;">
  <div class="card">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?><div class="alert alert-err"><?= safe($error) ?></div><?php endif; ?>
    <form class="stacked" method="post" action="/login.php">
      <label>Email</label>
      <input type="text" name="email" required>
      <label>Mật khẩu</label>
      <input type="password" name="password" required>
      <button type="submit" class="btn-primary" style="margin-top:20px;width:100%;">Đăng nhập</button>
    </form>
    <p style="margin-top:16px;font-size:14px;">Chưa có tài khoản? <a href="/register.php">Đăng ký</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
