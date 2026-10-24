<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\HttpException;
use App\Http\Request;
use App\Repository\TokenRepository;

final class Authenticator {
    public function __construct(private TokenRepository $tokens) {
    }

    /** Returns the id of the user who owns the Bearer token, or throws 401. */
    public function userId(Request $request): int
    {
        $token = $request->bearerToken();
        $userId = $token === null ? null : $this->tokens->findUserId($token);

        if ($userId === null) {
            throw new HttpException(401, 'Unauthorized');
        }

        return $userId;
    }
}
