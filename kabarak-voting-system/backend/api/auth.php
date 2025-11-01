<?php
declare(strict_types=1);

use KabarakVotingSystem\Backend\Classes\Database;
use KabarakVotingSystem\Backend\Classes\User;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json');

$pdo = Database::getConnection();
$userRepository = new User($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Extract action from query string first (used by login/register/logout via JavaScript)
// Then check payload (used by other endpoints)
$action = $_GET['action'] ?? null;

$requestBody = file_get_contents('php://input');
$payload = [];

if (!empty($requestBody)) {
    $decoded = json_decode($requestBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $payload = $decoded;
    }
}

// Merge traditional form data if present.
$payload = array_merge($_POST, $payload);

// If action wasn't in query string, check payload
if (!$action) {
    $action = $payload['action'] ?? null;
}

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($method === 'POST' && $action === 'login') {
    if (!kvs_validate_csrf($payload['csrf_token'] ?? null)) {
        respond(403, ['success' => false, 'message' => 'Invalid CSRF token.']);
    }

    $username = filter_var((string) ($payload['username'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = (string) ($payload['password'] ?? '');

    if ($username === '' || $password === '') {
        respond(400, ['success' => false, 'message' => 'Username and password are required.']);
    }

    $user = $userRepository->authenticate($username, $password);

    if (!$user) {
        respond(401, ['success' => false, 'message' => 'Invalid login credentials.']);
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'student_id' => $user['student_id'],
    ];

    respond(200, [
        'success' => true,
        'message' => 'Login successful.',
        'user' => $_SESSION['user'],
    ]);
}

if ($method === 'POST' && $action === 'register') {
    if (!kvs_validate_csrf($payload['csrf_token'] ?? null)) {
        respond(403, ['success' => false, 'message' => 'Invalid CSRF token.']);
    }

    $fullName = trim((string) ($payload['full_name'] ?? ''));
    $studentId = trim((string) ($payload['student_id'] ?? ''));
    $username = filter_var((string) ($payload['username'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = (string) ($payload['password'] ?? '');

    if ($fullName === '' || $studentId === '' || $username === '' || $password === '') {
        respond(400, ['success' => false, 'message' => 'All fields are required for registration.']);
    }

    try {
        $user = $userRepository->registerStudent($fullName, $studentId, $username, $password);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'student_id' => $user['student_id'],
        ];
        respond(201, [
            'success' => true,
            'message' => 'Registration successful.',
            'user' => $_SESSION['user'],
        ]);
    } catch (Throwable $exception) {
        respond(400, ['success' => false, 'message' => 'Registration failed: ' . $exception->getMessage()]);
    }
}

if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    respond(200, ['success' => true, 'message' => 'Logged out successfully.']);
}

if ($method === 'GET' && $action === 'status') {
    $user = $_SESSION['user'] ?? null;
    respond(200, [
        'authenticated' => (bool) $user,
        'user' => $user,
        'csrf_token' => kvs_csrf_token(),
    ]);
}

respond(405, ['success' => false, 'message' => 'Unsupported request.']);

