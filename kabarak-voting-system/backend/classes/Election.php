<?php
declare(strict_types=1);

namespace KabarakVotingSystem\Backend\Classes;

use PDO;
use RuntimeException;

class Election
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveElections(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM elections WHERE is_active = 1 AND datetime(start_at) <= datetime("now") AND datetime(end_at) >= datetime("now") ORDER BY start_at ASC');
        $elections = $stmt->fetchAll();

        return array_map(fn ($election) => $this->attachCandidates($election), $elections);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM elections ORDER BY created_at DESC');
        $elections = $stmt->fetchAll();

        return array_map(fn ($election) => $this->attachCandidates($election), $elections);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO elections (title, description, start_at, end_at, is_active) VALUES (:title, :description, :start_at, :end_at, :is_active)');
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_at' => $data['start_at'],
            ':end_at' => $data['end_at'],
            ':is_active' => $data['is_active'] ?? 1,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): array
    {
        $stmt = $this->pdo->prepare('UPDATE elections SET title = :title, description = :description, start_at = :start_at, end_at = :end_at, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_at' => $data['start_at'],
            ':end_at' => $data['end_at'],
            ':is_active' => $data['is_active'] ?? 1,
            ':id' => $id,
        ]);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM elections WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCandidates(int $electionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM candidates WHERE election_id = :election_id ORDER BY name ASC');
        $stmt->execute([':election_id' => $electionId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function addCandidate(int $electionId, array $candidate): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO candidates (election_id, name, photo, bio, manifesto) VALUES (:election_id, :name, :photo, :bio, :manifesto)');
        $stmt->execute([
            ':election_id' => $electionId,
            ':name' => $candidate['name'],
            ':photo' => $candidate['photo'] ?? null,
            ':bio' => $candidate['bio'] ?? null,
            ':manifesto' => $candidate['manifesto'] ?? null,
        ]);

        return $this->findCandidateById((int) $this->pdo->lastInsertId());
    }

    public function deleteCandidate(int $candidateId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM candidates WHERE id = :id');
        $stmt->execute([':id' => $candidateId]);
    }

    public function hasVoted(int $electionId, string $voterIdHash): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM votes WHERE election_id = :election_id AND voter_id_hash = :voter_id_hash');
        $stmt->execute([
            ':election_id' => $electionId,
            ':voter_id_hash' => $voterIdHash,
        ]);

        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function recordVote(int $electionId, int $candidateId, string $voterIdHash, string $blockHash): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO votes (election_id, candidate_id, voter_id_hash, block_hash) VALUES (:election_id, :candidate_id, :voter_id_hash, :block_hash)');
        $stmt->execute([
            ':election_id' => $electionId,
            ':candidate_id' => $candidateId,
            ':voter_id_hash' => $voterIdHash,
            ':block_hash' => $blockHash,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVoteStats(int $electionId): array
    {
        $stmt = $this->pdo->prepare('SELECT c.id AS candidate_id, c.name, COUNT(v.id) AS votes
            FROM candidates c
            LEFT JOIN votes v ON v.candidate_id = c.id
            WHERE c.election_id = :election_id
            GROUP BY c.id, c.name
            ORDER BY votes DESC');
        $stmt->execute([':election_id' => $electionId]);

        return $stmt->fetchAll();
    }

    public function totalVotesCast(int $electionId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM votes WHERE election_id = :election_id');
        $stmt->execute([':election_id' => $electionId]);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function totalRegisteredVoters(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS total FROM users WHERE role = "student"');
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM elections WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $election = $stmt->fetch();

        if (!$election) {
            throw new RuntimeException('Election not found.');
        }

        return $this->attachCandidates($election);
    }

    /**
     * @return array<string, mixed>
     */
    private function findCandidateById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM candidates WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $candidate = $stmt->fetch();

        if (!$candidate) {
            throw new RuntimeException('Candidate not found.');
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $election
     * @return array<string, mixed>
     */
    private function attachCandidates(array $election): array
    {
        $election['candidates'] = $this->getCandidates((int) $election['id']);
        return $election;
    }
}

