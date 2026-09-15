<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$me = require_role('employer');

$doc = $_GET['doc'] ?? '';

// [Path Traversal/LFI - Hard: double URL-encoding bypass]
// A previous pentest (finding JOBHUNT-133) flagged that this preview
// endpoint could read arbitrary files via "../" in `doc`. The shipped fix
// rejects any request whose value contains a literal "../" — but then
// calls urldecode() on it afterwards "to normalize paths some corporate
// proxies send double-encoded", and uses THAT decoded value to build the
// path. $_GET has already been decoded once by PHP itself, so a request
// with %252e%252e%252f arrives here as "%2e%2e%2f" — no literal "../",
// so it sails through the check — and only becomes a real "../" after
// the app's own urldecode() call below, which nothing re-checks.
if (strpos($doc, '../') !== false) {
    http_response_code(400);
    die('Invalid document path.');
}
$doc = urldecode($doc);

$path = __DIR__ . '/../../uploads/' . $doc;
if (!is_file($path)) {
    http_response_code(404);
    die('Document not found.');
}

$pageTitle = 'Xem trước CV';
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section container">
  <div class="card">
    <h2>Xem trước: <?= safe(basename($doc)) ?></h2>
    <div style="border:1px solid var(--border);border-radius:8px;padding:20px;background:#fafcfb;">
      <?php
        // Legacy "smart template" preview: early CV uploads on this
        // platform supported lightweight PHP short-echo mail-merge
        // placeholders (candidate name/title), so this has always used
        // include() rather than a plain file read to render them.
        // Nobody revisited that decision once CVs became opaque PDF/DOCX
        // blobs — which means any file reachable through `doc` executes
        // as PHP, not just displays. See README §9 (exploit chain).
        include $path;
      ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
