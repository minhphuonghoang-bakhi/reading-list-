<?php

declare(strict_types=1);

namespace App\Repository;

use App\Http\HttpException;

final class UserRepository
{
    public function __construct(private \PDO $pdo) {
    }
    /** register */
    public function create(string $name, string $email, string $passwordHash): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $email, $passwordHash]);
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {   // duplicate key = email exists
                throw new HttpException(400, 'Email is already registered');
            }
            throw $e;
        }

        return (int)$this->pdo->lastInsertId(); // Assign the auto-generated ID from the database to the $userId variable in PHP.
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash
         FROM users
         WHERE email = ?'
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }
}
