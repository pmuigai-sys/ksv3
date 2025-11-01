<?php
declare(strict_types=1);

use KabarakVotingSystem\Backend\Classes\Blockchain;
use KabarakVotingSystem\Backend\Classes\Database;
use KabarakVotingSystem\Backend\Classes\Election;
use KabarakVotingSystem\Backend\Classes\User;

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
$role = $sessionUser['role'] ?? 'guest';

$pdo = Database::getConnection();
$elections = new Election($pdo);
$userRepository = new User($pdo);
$blockchain = new Blockchain($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$body = file_get_contents('php://input');
$payload = [];

if (!empty($body)) {
    $decoded = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $payload = $decoded;
    }
}

$payload = array_merge($_POST, $payload);
$action = $_GET['action'] ?? ($payload['action'] ?? null);

if ($method === 'GET' && in_array($action, ['public', 'public_active'], true)) {
    if (!$sessionUser || $role !== 'student') {
        respond(403, ['success' => false, 'message' => 'Student access required.']);
    }

    if ($action === 'public_active') {
        respond(200, ['success' => true, 'data' => $elections->getActiveElections()]);
    }

    respond(200, ['success' => true, 'data' => $elections->getAll()]);
}

if (!$sessionUser || $role !== 'admin') {
    respond(403, ['success' => false, 'message' => 'Admin privileges required.']);
}

if ($method === 'GET' && $action === 'list') {
    $allElections = $elections->getAll();
    $sumVotes = 0;
    $activeCount = 0;

    foreach ($allElections as &$election) {
        $voteTotal = $elections->totalVotesCast((int) $election['id']);
        $election['total_votes'] = $voteTotal;
        $sumVotes += $voteTotal;
        if ((int) $election['is_active'] === 1) {
            $activeCount++;
        }
    }
    unset($election);

    $auditStmt = $pdo->query('SELECT a.action, a.payload, a.created_at, u.full_name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 50');
    $auditLogs = array_map(static function (array $row): array {
        $payload = [];
        if (!empty($row['payload'])) {
            $payload = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        }
        return [
            'action' => $row['action'],
            'payload' => $payload,
            'created_at' => $row['created_at'],
            'user_name' => $row['user_name'] ?? 'System',
        ];
    }, $auditStmt->fetchAll());

    $registered = $elections->totalRegisteredVoters();

    respond(200, [
        'success' => true,
        'data' => $allElections,
        'chain_valid' => $blockchain->isChainValid(),
        'meta' => [
            'total_elections' => count($allElections),
            'active_elections' => $activeCount,
            'total_votes' => $sumVotes,
            'registered_voters' => $registered,
        ],
        'audit_logs' => $auditLogs,
    ]);
}

if ($method === 'GET' && $action === 'export') {
    $electionId = (int) ($payload['election_id'] ?? $_GET['election_id'] ?? 0);
    if ($electionId <= 0) {
        respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
    }

    $stats = $elections->getVoteStats($electionId);
    $csvRows = ["Candidate,Votes"];
    foreach ($stats as $row) {
        $csvRows[] = sprintf('"%s",%d', str_replace('"', '""', $row['name']), (int) $row['votes']);
    }

    $csvContent = implode("\n", $csvRows);

    respond(200, [
        'success' => true,
        'filename' => 'election-' . $electionId . '-results.csv',
        'content_type' => 'text/csv',
        'data_url' => 'data:text/csv;base64,' . base64_encode($csvContent),
    ]);
}

if ($method === 'POST') {
    if (!kvs_validate_csrf($payload['csrf_token'] ?? null)) {
        respond(403, ['success' => false, 'message' => 'Invalid CSRF token.']);
    }
}

// Only process mutation actions (create, update, delete, etc.) for POST requests
if ($method !== 'POST' && in_array($action, ['create', 'update', 'delete', 'add_candidate', 'delete_candidate', 'toggle_active'], true)) {
    respond(405, ['success' => false, 'message' => 'Method not allowed. POST required for this action.']);
}

// If no action is specified and it's a POST request, return error
if ($method === 'POST' && !$action) {
    respond(400, ['success' => false, 'message' => 'Action parameter is required.']);
}

try {
    switch ($action) {
        case 'create':
            // Normalize datetime format from datetime-local (YYYY-MM-DDTHH:mm) to SQLite format (YYYY-MM-DD HH:mm:ss)
            $startAt = (string) ($payload['start_at'] ?? '');
            $endAt = (string) ($payload['end_at'] ?? '');
            
            // Convert T separator to space and ensure seconds are included
            if (strpos($startAt, 'T') !== false) {
                $startAt = str_replace('T', ' ', $startAt);
                if (substr_count($startAt, ':') === 1) {
                    $startAt .= ':00'; // Add seconds if missing
                }
            }
            if (strpos($endAt, 'T') !== false) {
                $endAt = str_replace('T', ' ', $endAt);
                if (substr_count($endAt, ':') === 1) {
                    $endAt .= ':00'; // Add seconds if missing
                }
            }
            
            $data = [
                'title' => trim((string) ($payload['title'] ?? '')),
                'description' => trim((string) ($payload['description'] ?? '')),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_active' => (int) ($payload['is_active'] ?? 1),
            ];

            if ($data['title'] === '' || $data['start_at'] === '' || $data['end_at'] === '') {
                respond(400, ['success' => false, 'message' => 'Title, start date, and end date are required.']);
            }

            $created = $elections->create($data);
            $userRepository->logAudit((int) $sessionUser['id'], 'ELECTION_CREATED', ['election' => $created]);
            respond(201, ['success' => true, 'message' => 'Election created.', 'data' => $created]);

        case 'update':
            $id = (int) ($payload['id'] ?? 0);
            if ($id <= 0) {
                respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
            }

            $data = [
                'title' => trim((string) ($payload['title'] ?? '')),
                'description' => trim((string) ($payload['description'] ?? '')),
                'start_at' => (string) ($payload['start_at'] ?? ''),
                'end_at' => (string) ($payload['end_at'] ?? ''),
                'is_active' => (int) ($payload['is_active'] ?? 1),
            ];

            $updated = $elections->update($id, $data);
            $userRepository->logAudit((int) $sessionUser['id'], 'ELECTION_UPDATED', ['election' => $updated]);
            respond(200, ['success' => true, 'message' => 'Election updated.', 'data' => $updated]);

        case 'delete':
            $id = (int) ($payload['id'] ?? 0);
            if ($id <= 0) {
                respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
            }

            $elections->delete($id);
            $userRepository->logAudit((int) $sessionUser['id'], 'ELECTION_DELETED', ['election_id' => $id]);
            respond(200, ['success' => true, 'message' => 'Election deleted.']);

        case 'add_candidate':
            $electionId = (int) ($payload['election_id'] ?? 0);
            if ($electionId <= 0) {
                respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
            }

            $candidate = [
                'name' => trim((string) ($payload['name'] ?? '')),
                'photo' => trim((string) ($payload['photo'] ?? '')),
                'bio' => trim((string) ($payload['bio'] ?? '')),
                'manifesto' => trim((string) ($payload['manifesto'] ?? '')),
            ];

            if ($candidate['name'] === '') {
                respond(400, ['success' => false, 'message' => 'Candidate name is required.']);
            }

            $createdCandidate = $elections->addCandidate($electionId, $candidate);
            $userRepository->logAudit((int) $sessionUser['id'], 'CANDIDATE_ADDED', ['candidate' => $createdCandidate]);
            respond(201, ['success' => true, 'message' => 'Candidate added.', 'data' => $createdCandidate]);

        case 'delete_candidate':
            $candidateId = (int) ($payload['candidate_id'] ?? 0);
            if ($candidateId <= 0) {
                respond(400, ['success' => false, 'message' => 'Invalid candidate identifier.']);
            }

            $elections->deleteCandidate($candidateId);
            $userRepository->logAudit((int) $sessionUser['id'], 'CANDIDATE_DELETED', ['candidate_id' => $candidateId]);
            respond(200, ['success' => true, 'message' => 'Candidate removed.']);

        case 'toggle_active':
            $id = (int) ($payload['id'] ?? 0);
            $isActive = (int) ($payload['is_active'] ?? 1);
            if ($id <= 0) {
                respond(400, ['success' => false, 'message' => 'Invalid election identifier.']);
            }

            $updated = $elections->update($id, [
                'title' => trim((string) ($payload['title'] ?? '')),
                'description' => trim((string) ($payload['description'] ?? '')),
                'start_at' => (string) ($payload['start_at'] ?? ''),
                'end_at' => (string) ($payload['end_at'] ?? ''),
                'is_active' => $isActive,
            ]);

            $userRepository->logAudit((int) $sessionUser['id'], 'ELECTION_STATUS_OVERRIDE', ['election' => $updated]);
            respond(200, ['success' => true, 'message' => 'Election status updated.', 'data' => $updated]);

        default:
            respond(405, ['success' => false, 'message' => 'Unsupported action.']);
    }
} catch (Throwable $exception) {
    respond(400, ['success' => false, 'message' => $exception->getMessage()]);
}

