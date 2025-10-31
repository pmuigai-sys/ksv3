<?php
declare(strict_types=1);

use DateTimeImmutable;
use KabarakVotingSystem\Backend\Classes\Blockchain;
use KabarakVotingSystem\Backend\Classes\Database;
use KabarakVotingSystem\Backend\Classes\Election;
use KabarakVotingSystem\Backend\Classes\User;
use Throwable;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Election.php';
require_once __DIR__ . '/../classes/Blockchain.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    respond(403, ['success' => false, 'message' => 'Authentication required.']);
}

$role = $sessionUser['role'] ?? 'guest';
if ($role !== 'student' && $role !== 'admin') {
    respond(403, ['success' => false, 'message' => 'Access denied.']);
}

$pdo = Database::getConnection();
$elections = new Election($pdo);
$blockchain = new Blockchain($pdo);
$userRepository = new User($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

$body = file_get_contents('php://input');
$payload = [];
if (!empty($body)) {
    $decoded = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $payload = $decoded;
    }
}

$payload = array_merge($_POST, $payload);

$studentId = (string) ($sessionUser['student_id'] ?? $sessionUser['username']);
$voterHash = hash('sha256', $studentId . KVS_APP_NAME);

if ($method === 'GET' && $action === 'stats') {
    $electionId = (int) ($_GET['election_id'] ?? $payload['election_id'] ?? 0);
    if ($electionId <= 0) {
        respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
    }

    $stats = $elections->getVoteStats($electionId);
    $totalVotes = $elections->totalVotesCast($electionId);
    $registered = $elections->totalRegisteredVoters();
    $turnout = $registered > 0 ? round(($totalVotes / $registered) * 100, 2) : 0;

    respond(200, [
        'success' => true,
        'data' => $stats,
        'total_votes' => $totalVotes,
        'registered_voters' => $registered,
        'turnout_percentage' => $turnout,
        'chain_valid' => $blockchain->isChainValid(),
    ]);
}

if ($role === 'student' && $method === 'GET' && $action === 'history') {
    $stmt = $pdo->prepare('SELECT e.title, v.created_at FROM votes v JOIN elections e ON e.id = v.election_id WHERE v.voter_id_hash = :hash ORDER BY v.created_at DESC');
    $stmt->execute([':hash' => $voterHash]);
    respond(200, ['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($role === 'student' && $method === 'POST' && $action === 'vote') {
    if (!kvs_validate_csrf($payload['csrf_token'] ?? null)) {
        respond(403, ['success' => false, 'message' => 'Invalid CSRF token.']);
    }

    $electionId = (int) ($payload['election_id'] ?? 0);
    $candidateId = (int) ($payload['candidate_id'] ?? 0);

    if ($electionId <= 0 || $candidateId <= 0) {
        respond(400, ['success' => false, 'message' => 'Election and candidate are required.']);
    }

    try {
        $election = $elections->findById($electionId);
    } catch (Throwable $exception) {
        respond(404, ['success' => false, 'message' => 'Election not found.']);
    }

    $now = new DateTimeImmutable('now');
    $start = new DateTimeImmutable($election['start_at']);
    $end = new DateTimeImmutable($election['end_at']);

    if (!$election['is_active']) {
        respond(403, ['success' => false, 'message' => 'Election is not active.']);
    }

    if ($now < $start) {
        respond(403, ['success' => false, 'message' => 'Election has not started.']);
    }

    if ($now > $end) {
        respond(403, ['success' => false, 'message' => 'Election has ended.']);
    }

    $candidateIds = array_column($election['candidates'], 'id');
    if (!in_array($candidateId, array_map('intval', $candidateIds), true)) {
        respond(400, ['success' => false, 'message' => 'Invalid candidate selection.']);
    }

    if ($elections->hasVoted($electionId, $voterHash)) {
        respond(409, ['success' => false, 'message' => 'You have already voted in this election.']);
    }

    $voteData = [
        'type' => 'vote',
        'election_id' => $electionId,
        'candidate_id' => $candidateId,
        'voter_hash' => $voterHash,
        'timestamp' => gmdate('c'),
    ];

    try {
        $block = $blockchain->addBlock($voteData);
        $elections->recordVote($electionId, $candidateId, $voterHash, $block['hash']);
        $userRepository->logAudit((int) $sessionUser['id'], 'VOTE_CAST', ['election_id' => $electionId, 'block_hash' => $block['hash']]);

        respond(201, [
            'success' => true,
            'message' => 'Vote recorded successfully.',
            'block_hash' => $block['hash'],
            'block_index' => $block['block_index'],
        ]);
    } catch (Throwable $exception) {
        respond(500, ['success' => false, 'message' => 'Failed to record vote: ' . $exception->getMessage()]);
    }
}

respond(405, ['success' => false, 'message' => 'Unsupported request.']);

