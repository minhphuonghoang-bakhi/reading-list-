<?php

declare(strict_types=1);

namespace App\Repository;

final class TokenRepository
{
    public function __construct(private \PDO $pdo) {
    }
    /** Creates a token for the user and returns the plain value (only its hash is stored). */
    public function createForUser(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare('INSERT INTO api_tokens (user_id, token_hash) VALUES (?, ?)');
        $stmt->execute([$userId, hash('sha256', $token)]);

        return $token;
    }

    /** Returns the user id that owns the token, or null if the token is unknown. */
    public function findUserId(string $token): ?int
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM api_tokens WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);
        $userId = $stmt->fetchColumn();

        return $userId === false ? null : (int)$userId;
    }

    /** Deletes the token. Returns false if it did not exist. */
    public function delete(string $token): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);

        return $stmt->rowCount() > 0;
    }
}
