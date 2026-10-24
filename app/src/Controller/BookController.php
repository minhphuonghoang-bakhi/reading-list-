<?php

declare(strict_types=1);

namespace App\Controller;
use App\Model\Book;

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

    public function list(Request $request, array $params = []): Response
    {
        $userId = $this->authenticator->userId($request);
        $query = BookValidator::listQuery($request->query);

        $result = $this->books->list($userId, $query['page'], $query['limit'], $query['tag']);

        return Response::json([
            'data' => array_map(fn (Book $book) => $book->toArray(), $result['books']),
            'page' => $query['page'],
            'limit' => $query['limit'],
            'total' => $result['total'],
        ]);
    }
}
