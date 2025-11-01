<?php
declare(strict_types=1);

use KabarakVotingSystem\Backend\Classes\Blockchain;
use KabarakVotingSystem\Backend\Classes\Database;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Blockchain.php';

header('Content-Type: application/json');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser || ($sessionUser['role'] ?? '') !== 'admin') {
    respond(403, ['success' => false, 'message' => 'Admin privileges required.']);
}

$pdo = Database::getConnection();
$blockchain = new Blockchain($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? 'chain';

if ($method === 'GET' && $action === 'chain') {
    respond(200, [
        'success' => true,
        'data' => $blockchain->getChain(),
    ]);
}

if ($method === 'GET' && $action === 'verify') {
    respond(200, [
        'success' => true,
        'valid' => $blockchain->isChainValid(),
    ]);
}

respond(405, ['success' => false, 'message' => 'Unsupported request.']);

