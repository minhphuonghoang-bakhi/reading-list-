<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\AuthService;
use App\Validation\AuthValidator;

final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function register(Request $request, array $params = []): Response
    {
        $data = AuthValidator::register($request->json());
        $token = $this->auth->register($data['name'], $data['email'], $data['password']);

        return Response::json(['token' => $token], 201);
    }
}
