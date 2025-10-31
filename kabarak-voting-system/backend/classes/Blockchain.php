<?php
declare(strict_types=1);

namespace KabarakVotingSystem\Backend\Classes;

use PDO;
use RuntimeException;

require_once __DIR__ . '/../config/config.php';

class Blockchain
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieve the blockchain ordered by index.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getChain(): array
    {
        $stmt = $this->pdo->query('SELECT block_index, timestamp, nonce, hash, previous_hash, data FROM blocks ORDER BY block_index ASC');
        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            $row['data'] = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);
            return $row;
        }, $rows);
    }

    /**
     * Add a new block to the chain with the provided data payload.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function addBlock(array $data): array
    {
        $chain = $this->getChain();
        if (empty($chain)) {
            throw new RuntimeException('Blockchain not initialized.');
        }

        $previousBlock = end($chain);
        $index = (int) $previousBlock['block_index'] + 1;
        $previousHash = (string) $previousBlock['hash'];
        $timestamp = gmdate('c');

        [$nonce, $hash] = $this->mineBlock($index, $timestamp, $data, $previousHash);

        $this->insertBlock($index, $timestamp, $nonce, $hash, $previousHash, $data);

        return [
            'block_index' => $index,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'hash' => $hash,
            'previous_hash' => $previousHash,
            'data' => $data,
        ];
    }

    /**
     * Basic proof-of-work implementation to find a nonce producing a hash with a set difficulty.
     *
     * @param array<string, mixed> $data
     * @return array{0:int,1:string}
     */
    public function mineBlock(int $index, string $timestamp, array $data, string $previousHash): array
    {
        $difficultyPrefix = str_repeat('0', KVS_BLOCK_DIFFICULTY);
        $nonce = 0;

        while (true) {
            $hash = $this->hashBlock($index, $timestamp, $data, $previousHash, $nonce);
            if (strpos($hash, $difficultyPrefix) === 0) {
                return [$nonce, $hash];
            }
            $nonce++;
        }
    }

    /**
     * Verify the integrity of the chain.
     */
    public function isChainValid(): bool
    {
        $chain = $this->getChain();
        $expectedPrefix = str_repeat('0', KVS_BLOCK_DIFFICULTY);

        for ($i = 1, $len = count($chain); $i < $len; $i++) {
            $current = $chain[$i];
            $previous = $chain[$i - 1];

            $hash = $this->hashBlock(
                (int) $current['block_index'],
                (string) $current['timestamp'],
                (array) $current['data'],
                (string) $current['previous_hash'],
                (int) $current['nonce']
            );

            if ($hash !== $current['hash']) {
                return false;
            }

            if (strpos($hash, $expectedPrefix) !== 0) {
                return false;
            }

            if ($current['previous_hash'] !== $previous['hash']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert block properties into a hash string.
     *
     * @param array<string, mixed> $data
     */
    public function hashBlock(int $index, string $timestamp, array $data, string $previousHash, int $nonce): string
    {
        $payload = $index . $timestamp . json_encode($data, JSON_THROW_ON_ERROR) . $previousHash . $nonce;

        return hash('sha256', $payload);
    }

    /**
     * Insert the mined block into persistent storage.
     *
     * @param array<string, mixed> $data
     */
    private function insertBlock(int $index, string $timestamp, int $nonce, string $hash, string $previousHash, array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO blocks (block_index, timestamp, nonce, hash, previous_hash, data) VALUES (:block_index, :timestamp, :nonce, :hash, :previous_hash, :data)');
        $stmt->execute([
            ':block_index' => $index,
            ':timestamp' => $timestamp,
            ':nonce' => $nonce,
            ':hash' => $hash,
            ':previous_hash' => $previousHash,
            ':data' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);
    }
}

