<?php
declare(strict_types=1);

use KabarakVotingSystem\Backend\Classes\Database;

require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/classes/Database.php';

Database::getConnection(); // Ensure database initialization

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
$page = $_GET['page'] ?? null;

function render_template(string $template, array $data = []): void
{
    extract($data);
    require $template;
}

if (!$user) {
    render_template(__DIR__ . '/templates/shared/login.php', [
        'pageTitle' => 'Login',
        'bodyClass' => 'auth-page',
    ]);
    exit;
}

if ($role === 'admin') {
    switch ($page) {
        case 'elections':
            render_template(__DIR__ . '/templates/admin/elections.php', ['pageTitle' => 'Manage Elections', 'bodyClass' => 'admin-page elections-page']);
            break;
        case 'blockchain':
            render_template(__DIR__ . '/templates/admin/blockchain.php', ['pageTitle' => 'Blockchain Ledger', 'bodyClass' => 'admin-page blockchain-page']);
            break;
        default:
            render_template(__DIR__ . '/templates/admin/dashboard.php', ['pageTitle' => 'Admin Dashboard', 'bodyClass' => 'admin-page dashboard']);
    }
    exit;
}

// Student dashboard
switch ($page) {
    case 'vote':
        render_template(__DIR__ . '/templates/user/vote.php', ['pageTitle' => 'Cast Your Vote', 'bodyClass' => 'user-page vote-page']);
        break;
    case 'stats':
        render_template(__DIR__ . '/templates/user/stats.php', ['pageTitle' => 'Live Election Stats', 'bodyClass' => 'user-page stats-page']);
        break;
    default:
        render_template(__DIR__ . '/templates/user/home.php', ['pageTitle' => 'Student Dashboard', 'bodyClass' => 'user-page home-page']);
}

