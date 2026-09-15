<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');
$pageTitle = 'Xác thực doanh nghiệp';
$error = null;
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['doc'])) {
    $file = $_FILES['doc'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Lỗi tải file lên.';
    } elseif ($ext !== 'pdf') {
        $error = 'Chỉ chấp nhận file PDF (giấy phép kinh doanh).';
    } else {
        // [Upload-Hard: magic-byte check bypassed via polyglot]
        // After the CV-upload finding (apply.php — extension/MIME only)
        // was reported, this endpoint was written to actually inspect
        // the file instead of trusting metadata: it opens the upload and
        // checks that it *starts* with the real PDF signature. That's a
        // real improvement over apply.php — but it only ever looks at
        // the first 4 bytes. A file that legitimately starts with
        // "%PDF-1.4" and then contains anything at all afterwards —
        // including PHP — still passes, because nothing here reads past
        // the header to confirm the rest of the file is actually a PDF.
        $handle = fopen($file['tmp_name'], 'rb');
        $magic = fread($handle, 4);
        fclose($handle);

        if ($magic !== '%PDF') {
            $error = 'File không đúng định dạng PDF (magic bytes không hợp lệ).';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File tối đa 5MB.';
        } else {
            $storedName = 'verify_' . $me['id'] . '_' . bin2hex(random_bytes(6)) . '.pdf';
            $dest = __DIR__ . '/../../uploads/' . $storedName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $ok = 'Tài liệu đã được tải lên, đang chờ admin xác thực.';
            } else {
                $error = 'Không thể lưu file.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="dash-layout">
    <div class="dash-nav card">
      <a href="/dashboard/employer/index.php">Tổng quan</a>
      <a href="/dashboard/employer/post_job.php">Đăng tin mới</a>
      <a href="/dashboard/employer/candidates.php">Ứng viên</a>
      <a href="/dashboard/employer/webhook_test.php">Webhook &amp; công cụ</a>
      <a href="/dashboard/employer/verify_doc.php" class="active">Xác thực doanh nghiệp</a>
      <a href="/dashboard/employer/verify_website.php">Xác thực website</a>
    </div>
    <div class="card">
      <h2>Tải lên giấy phép kinh doanh</h2>
      <p style="color:var(--ink-soft);font-size:14px;">Xác thực doanh nghiệp giúp tin tuyển dụng của bạn hiển thị huy hiệu "Đã xác thực".</p>
      <?php if ($error): ?><div class="alert alert-err"><?= safe($error) ?></div><?php endif; ?>
      <?php if ($ok): ?><div class="alert alert-ok"><?= safe($ok) ?></div><?php endif; ?>
      <form class="stacked" method="post" action="/dashboard/employer/verify_doc.php" enctype="multipart/form-data">
        <label>Giấy phép kinh doanh (PDF)</label>
        <input type="file" name="doc" required accept=".pdf">
        <button type="submit" class="btn-primary" style="margin-top:20px;">Tải lên</button>
      </form>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
