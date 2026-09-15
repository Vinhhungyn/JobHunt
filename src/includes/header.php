<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

send_csp_header();
$me = current_user();
$pageTitle = $pageTitle ?? 'JobHunt';
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= safe($pageTitle) ?> · JobHunt</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="/index.php" class="brand">Job<span>Hunt</span></a>
    <nav class="main-nav">
      <a href="/index.php">Việc làm</a>
      <a href="/companies.php">Công ty</a>
      <?php if ($me && $me['role'] === 'employer'): ?>
        <a href="/dashboard/employer/index.php">Bảng điều khiển</a>
        <a href="/dashboard/employer/post_job.php">Đăng tin</a>
      <?php elseif ($me && $me['role'] === 'candidate'): ?>
        <a href="/dashboard/candidate/index.php">Hồ sơ của tôi</a>
        <a href="/dashboard/candidate/applications.php">Đơn ứng tuyển</a>
      <?php elseif ($me && $me['role'] === 'admin'): ?>
        <a href="/dashboard/admin/index.php">Quản trị</a>
      <?php endif; ?>
    </nav>
    <div class="nav-actions">
      <?php if ($me): ?>
        <a href="/messages.php" class="btn-ghost">Tin nhắn</a>
        <span class="user-chip"><?= safe($me['full_name']) ?></span>
        <a href="/logout.php" class="btn-outline">Đăng xuất</a>
      <?php else: ?>
        <a href="/login.php" class="btn-outline">Đăng nhập</a>
        <a href="/register.php" class="btn-primary">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main>
<?php
$flashOk = flash('ok');
$flashErr = flash('error');
if ($flashOk): ?>
  <div class="container"><div class="alert alert-ok"><?= safe($flashOk) ?></div></div>
<?php endif;
if ($flashErr): ?>
  <div class="container"><div class="alert alert-err"><?= safe($flashErr) ?></div></div>
<?php endif; ?>
