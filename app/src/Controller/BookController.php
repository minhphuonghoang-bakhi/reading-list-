<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\Authenticator;
use App\Service\BookService;
use App\Validation\BookValidator;

final class BookController {
    public function __construct(
        private BookService $books,
        private Authenticator $authenticator,
    ) {
    }

    public function create(Request $request, array $params = []): Response {
        $userId = $this->authenticator->userId($request);
        $data = BookValidator::create($request->json());

        $book = $this->books->create($userId, $data['title'], $data['description'], $data['tags']);

        return Response::json($book->toArray(), 201);
    }
}
