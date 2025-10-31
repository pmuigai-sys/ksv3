<?php
declare(strict_types=1);

namespace KabarakVotingSystem\Backend\Classes;

use PDO;
use RuntimeException;

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Authenticate a user by username/password.
     *
     * @return array<string, mixed>|null
     */
    public function authenticate(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    /**
     * Register a student user.
     *
     * @return array<string, mixed>
     */
    public function registerStudent(string $fullName, string $studentId, string $username, string $password): array
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash, role, full_name, student_id) VALUES (:username, :password_hash, :role, :full_name, :student_id)');
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':role' => 'student',
            ':full_name' => $fullName,
            ':student_id' => $studentId,
        ]);

        return $this->getById((int) $this->pdo->lastInsertId());
    }

    /**
     * Fetch a user by identifier.
     *
     * @return array<string, mixed>
     */
    public function getById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        return $user;
    }

    /**
     * Write an audit entry.
     *
     * @param array<string, mixed>|null $payload
     */
    public function logAudit(int $userId, string $action, ?array $payload = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_logs (user_id, action, payload) VALUES (:user_id, :action, :payload)');
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':payload' => $payload ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
        ]);
    }
}

