<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Http\HttpException;

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

    /** Checks credentials and returns a new auth token. */
    public function login(string $email, string $password): string
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            /**  Perform a dummy password hash to reduce timing differences
             * and prevent attackers from inferring whether an email exists.
             */
            password_hash($password, PASSWORD_DEFAULT);
            throw new HttpException(401, 'Invalid email or password');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new HttpException(401, 'Invalid email or password');
        }

        return $this->tokens->createForUser((int) $user['id']);
    }
}
