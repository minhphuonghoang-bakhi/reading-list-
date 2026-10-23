<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TokenRepository;
use App\Repository\UserRepository;

final class AuthService {
    public function __construct(
        private UserRepository  $users,
        private TokenRepository $tokens,) {
    }

    /** Registers a user and returns an auth token. */
    public function register(string $name, string $email, string $password): string
    {
        // create user
        $userId = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
        // create token for user
        return $this->tokens->createForUser($userId); //return token
    }
}
