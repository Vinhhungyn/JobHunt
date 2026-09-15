<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$me = require_role('candidate');
$pageTitle = 'Ứng tuyển';
$error = null;
$jobId = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT j.id, j.title, e.company_name FROM jobs j JOIN employer_profiles e ON e.user_id = j.employer_id WHERE j.id = ? AND j.status = 'approved'");
mysqli_stmt_bind_param($stmt, 'i', $jobId);
mysqli_stmt_execute($stmt);
$job = mysqli_stmt_get_result($stmt)->fetch_assoc();
if (!$job) {
    http_response_code(404);
    die('Không tìm thấy tin tuyển dụng.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv'])) {
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $file = $_FILES['cv'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Lỗi tải file lên.';
    } else {
        // [Upload-Medium: extension + client MIME only, no magic-byte check]
        // The team's checklist for this form (from an internal wiki page
        // titled "Secure file upload 101") says: whitelist the extension,
        // whitelist the MIME type, cap the size. That's what's below.
        // What it misses: $file['type'] is the Content-Type header the
        // *browser* sent for the upload — entirely attacker-controlled —
        // and the extension check only looks at the name the client
        // chose to send, not at the file's actual content. Nothing here
        // ever opens the file and looks at its first bytes.
        $allowedExt = ['pdf', 'doc', 'docx'];
        $allowedMime = ['application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = $file['type'];

        if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
            $error = 'Chỉ chấp nhận file PDF hoặc DOCX.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File tối đa 5MB.';
        } else {
            $storedName = 'cv_' . $me['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $storedName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt = mysqli_prepare($conn, 'INSERT INTO applications (job_id, candidate_id, cv_path, cover_letter) VALUES (?, ?, ?, ?)');
                $cvPath = 'uploads/' . $storedName;
                mysqli_stmt_bind_param($stmt, 'iiss', $jobId, $me['id'], $cvPath, $coverLetter);
                mysqli_stmt_execute($stmt);
                flash('ok', 'Nộp hồ sơ thành công!');
                redirect('/dashboard/candidate/applications.php');
            } else {
                $error = 'Không thể lưu file, vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Ứng tuyển: ' . $job['title'];
require_once __DIR__ . '/includes/header.php';
?>
<section class="section container" style="max-width:520px;">
  <div class="card">
    <h2>Ứng tuyển: <?= safe($job['title']) ?></h2>
    <p class="company"><?= safe($job['company_name']) ?></p>
    <?php if ($error): ?><div class="alert alert-err"><?= safe($error) ?></div><?php endif; ?>
    <form class="stacked" method="post" action="/apply.php?job_id=<?= (int)$job['id'] ?>" enctype="multipart/form-data">
      <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
      <label>Thư giới thiệu (không bắt buộc)</label>
      <textarea name="cover_letter" rows="4"></textarea>
      <label>CV (PDF hoặc DOCX, tối đa 5MB)</label>
      <input type="file" name="cv" required accept=".pdf,.doc,.docx">
      <p style="font-size:13px;margin-top:8px;">Chưa có CV? <a href="/download.php?file=cv_template_backend.txt">Tải mẫu CV</a></p>
      <button type="submit" class="btn-primary" style="margin-top:20px;width:100%;">Gửi hồ sơ</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
