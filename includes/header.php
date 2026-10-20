<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$notice = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Haven Hostel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="app-body">
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="main-area">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
            <div class="topbar-title"><span class="eyebrow">HOSTEL ADMINISTRATION</span><strong><?= e($pageTitle) ?></strong></div>
            <div class="topbar-user"><span class="avatar"><?= e(strtoupper(substr((string) ($_SESSION['admin_name'] ?? 'A'), 0, 1))) ?></span><span><?= e($_SESSION['admin_name'] ?? 'Administrator') ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="logout-link" type="submit">Sign out</button></form></div>
        </header>
        <main class="content">
            <?php if ($notice): ?>
                <div class="notice notice-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?><button type="button" class="notice-close" aria-label="Dismiss">×</button></div>
            <?php endif; ?>