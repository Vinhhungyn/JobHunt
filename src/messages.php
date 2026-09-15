<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$me = require_login();
$pageTitle = 'Tin nhắn';

$withId = (int)($_GET['with'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = (int)($_POST['to'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($to > 0 && $content !== '') {
        $stmt = mysqli_prepare($conn, 'INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iis', $me['id'], $to, $content);
        mysqli_stmt_execute($stmt);
    }
    redirect('/messages.php?with=' . $to);
}

// Conversation partners: anyone we've exchanged messages with.
$stmt = mysqli_prepare($conn, "SELECT DISTINCT u.id, u.full_name, u.role FROM users u
    WHERE u.id IN (SELECT receiver_id FROM messages WHERE sender_id = ?
                    UNION SELECT sender_id FROM messages WHERE receiver_id = ?)");
mysqli_stmt_bind_param($stmt, 'ii', $me['id'], $me['id']);
mysqli_stmt_execute($stmt);
$contacts = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

$thread = [];
$withUser = null;
if ($withId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT id, full_name, role FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $withId);
    mysqli_stmt_execute($stmt);
    $withUser = mysqli_stmt_get_result($stmt)->fetch_assoc();

    $stmt = mysqli_prepare($conn, 'SELECT sender_id, content, created_at FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC');
    mysqli_stmt_bind_param($stmt, 'iiii', $me['id'], $withId, $withId, $me['id']);
    mysqli_stmt_execute($stmt);
    $thread = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <h4>Hội thoại</h4>
      <?php foreach ($contacts as $c): ?>
        <a href="/messages.php?with=<?= (int)$c['id'] ?>" class="<?= $withId === (int)$c['id'] ? 'active' : '' ?>"><?= safe($c['full_name']) ?></a>
      <?php endforeach; ?>
      <?php if (!$contacts): ?><p style="font-size:13px;color:var(--ink-soft);">Chưa có hội thoại nào.</p><?php endif; ?>
    </div>
    <div class="card">
      <?php if (!$withUser): ?>
        <p>Chọn một hội thoại để xem tin nhắn.</p>
      <?php else: ?>
        <h3>Trò chuyện với <?= safe($withUser['full_name']) ?></h3>
        <div style="max-height:400px;overflow-y:auto;margin-bottom:16px;">
          <?php foreach ($thread as $m): ?>
            <div class="comment">
              <span class="who"><?= $m['sender_id'] == $me['id'] ? 'Bạn' : safe($withUser['full_name']) ?></span>
              <span class="when"><?= safe($m['created_at']) ?></span>
              <p><?= nl2br(safe($m['content'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="post" action="/messages.php" class="stacked">
          <input type="hidden" name="to" value="<?= (int)$withUser['id'] ?>">
          <textarea name="content" rows="2" placeholder="Nhập tin nhắn..." required></textarea>
          <button type="submit" class="btn-primary" style="margin-top:10px;">Gửi</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
