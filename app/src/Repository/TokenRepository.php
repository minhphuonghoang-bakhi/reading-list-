<?php

declare(strict_types=1);

namespace App\Repository;

final class TokenRepository
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** Creates a token for the user and returns the plain value (only its hash is stored). */
    public function createForUser(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare('INSERT INTO api_tokens (user_id, token_hash) VALUES (?, ?)');
        $stmt->execute([$userId, hash('sha256', $token)]);

        return $token;
    }
}
