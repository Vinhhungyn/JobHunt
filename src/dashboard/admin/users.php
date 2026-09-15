<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('admin');
$pageTitle = 'Quản lý người dùng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'ban' || $action === 'unban') {
        $banned = $action === 'ban' ? 1 : 0;
        $stmt = mysqli_prepare($conn, 'UPDATE users SET is_banned = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $banned, $uid);
        mysqli_stmt_execute($stmt);
        $stmt = mysqli_prepare($conn, 'INSERT INTO admin_logs (action, detail, actor) VALUES (?, ?, ?)');
        $detail = "user_id=$uid";
        $act = $action === 'ban' ? 'ban_user' : 'unban_user';
        mysqli_stmt_bind_param($stmt, 'sss', $act, $detail, $me['email']);
        mysqli_stmt_execute($stmt);
    }
    redirect('/dashboard/admin/users.php');
}

$result = mysqli_query($conn, 'SELECT id, email, role, full_name, is_verified, is_banned, created_at FROM users ORDER BY created_at DESC');
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/admin/index.php">Tổng quan</a>
      <a href="/dashboard/admin/users.php" class="active">Người dùng</a>
      <a href="/dashboard/admin/approve_jobs.php">Duyệt tin</a>
      <a href="/dashboard/admin/logs.php">Nhật ký hệ thống</a>
      <a href="/dashboard/admin/diagnostics.php">Công cụ chẩn đoán</a>
    </div>
    <div class="card">
      <h2>Người dùng</h2>
      <table class="data">
        <thead><tr><th>Email</th><th>Tên</th><th>Vai trò</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= safe($u['email']) ?></td>
            <td><?= safe($u['full_name']) ?></td>
            <td><?= safe($u['role']) ?></td>
            <td><?= $u['is_banned'] ? '<span class="badge rejected">Đã khoá</span>' : '<span class="badge approved">Hoạt động</span>' ?></td>
            <td>
              <form method="post" action="/dashboard/admin/users.php" style="display:inline;">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="<?= $u['is_banned'] ? 'unban' : 'ban' ?>">
                <button type="submit" class="btn-outline"><?= $u['is_banned'] ? 'Mở khoá' : 'Khoá' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
