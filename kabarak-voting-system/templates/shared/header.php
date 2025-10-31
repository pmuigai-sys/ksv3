<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/config/config.php';

$pageTitle = $pageTitle ?? KVS_APP_NAME;
$bodyClass = $bodyClass ?? '';
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';

$cssFiles = ['/assets/css/style.css'];
if ($role === 'admin') {
    $cssFiles[] = '/assets/css/admin.css';
} elseif ($role === 'student') {
    $cssFiles[] = '/assets/css/user.css';
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle . ' | ' . KVS_APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach ($cssFiles as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>" data-user-role="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
<header class="app-header">
    <div class="app-header__brand">
        <span class="logo">KVS</span>
        <div>
            <h1>Kabarak Voting</h1>
            <p>Secure. Transparent. Instant.</p>
        </div>
    </div>
    <nav class="app-header__nav">
        <?php if ($role === 'admin'): ?>
            <a href="/index.php" class="nav-link" data-nav="dashboard">Dashboard</a>
            <a href="/index.php?page=elections" class="nav-link" data-nav="elections">Elections</a>
            <a href="/index.php?page=blockchain" class="nav-link" data-nav="blockchain">Blockchain</a>
        <?php elseif ($role === 'student'): ?>
            <a href="/index.php" class="nav-link" data-nav="home">Home</a>
            <a href="/index.php?page=vote" class="nav-link" data-nav="vote">Vote</a>
            <a href="/index.php?page=stats" class="nav-link" data-nav="stats">Live Stats</a>
        <?php endif; ?>
    </nav>
    <div class="app-header__profile">
        <?php if ($user): ?>
            <span class="avatar" aria-hidden="true"><?= strtoupper(substr((string) ($user['full_name'] ?? $user['username']), 0, 1)) ?></span>
            <div>
                <p class="name"><?= htmlspecialchars((string) ($user['full_name'] ?? $user['username']), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="role"><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <button id="logoutButton" class="ghost-button" type="button">Sign out</button>
        <?php endif; ?>
    </div>
</header>
<main class="app-main" id="appMain">

