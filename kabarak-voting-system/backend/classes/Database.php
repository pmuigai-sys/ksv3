<?php
declare(strict_types=1);

namespace KabarakVotingSystem\Backend\Classes;

use PDO;
use PDOException;

require_once __DIR__ . '/../config/config.php';

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        try {
            $dsn = 'sqlite:' . KVS_DB_PATH;
            self::$connection = new PDO($dsn);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->exec('PRAGMA foreign_keys = ON;');

            self::initializeSchema(self::$connection);
        } catch (PDOException $exception) {
            http_response_code(500);
            die('Database connection failed: ' . $exception->getMessage());
        }

        return self::$connection;
    }

    private static function initializeSchema(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ("admin", "student")),
            full_name TEXT,
            student_id TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );');

        $pdo->exec('CREATE TABLE IF NOT EXISTS elections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            start_at TEXT NOT NULL,
            end_at TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );');

        $pdo->exec('CREATE TABLE IF NOT EXISTS candidates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            election_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            photo TEXT,
            bio TEXT,
            manifesto TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE
        );');

        $pdo->exec('CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            election_id INTEGER NOT NULL,
            voter_id_hash TEXT NOT NULL,
            candidate_id INTEGER NOT NULL,
            block_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(election_id, voter_id_hash),
            FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE,
            FOREIGN KEY(candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
        );');

        $pdo->exec('CREATE TABLE IF NOT EXISTS blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            block_index INTEGER NOT NULL,
            timestamp TEXT NOT NULL,
            nonce INTEGER NOT NULL,
            hash TEXT NOT NULL,
            previous_hash TEXT NOT NULL,
            data TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(block_index)
        );');

        $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            payload TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );');

        self::ensureDefaultAdmin($pdo);
        self::ensureGenesisBlock($pdo);
    }

    private static function ensureDefaultAdmin(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role = "admin"');
        $count = (int) ($stmt->fetch()['total'] ?? 0);

        if ($count === 0) {
            $passwordHash = password_hash('Admin@123', PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (username, password_hash, role, full_name) VALUES (:username, :password_hash, :role, :full_name)');
            $insert->execute([
                ':username' => 'admin@kabarak.ac.ke',
                ':password_hash' => $passwordHash,
                ':role' => 'admin',
                ':full_name' => 'System Administrator',
            ]);
        }
    }

    private static function ensureGenesisBlock(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM blocks');
        $count = (int) ($stmt->fetch()['total'] ?? 0);

        if ($count === 0) {
            $genesisData = json_encode([
                'type' => 'genesis',
                'message' => 'Kabarak Voting System Genesis Block',
            ], JSON_THROW_ON_ERROR);

            $timestamp = gmdate('c');
            $index = 0;
            $nonce = 0;
            $previousHash = '0';
            $hash = hash('sha256', $index . $timestamp . $genesisData . $previousHash . $nonce);

            $insert = $pdo->prepare('INSERT INTO blocks (block_index, timestamp, nonce, hash, previous_hash, data) VALUES (:block_index, :timestamp, :nonce, :hash, :previous_hash, :data)');
            $insert->execute([
                ':block_index' => $index,
                ':timestamp' => $timestamp,
                ':nonce' => $nonce,
                ':hash' => $hash,
                ':previous_hash' => $previousHash,
                ':data' => $genesisData,
            ]);
        }
    }
}

