<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Book;
use App\Repository\BookRepository;
use App\Repository\TagRepository;
use App\Http\HttpException;

final class BookService {
    public function __construct(
        private \PDO $pdo,
        private BookRepository $books,
        private TagRepository $tags,) {
    }

    /** @param string[] $tagNames */
    public function create(int $userId, string $title, ?string $description, array $tagNames): Book {
        $this->pdo->beginTransaction(); // Atomicity
        try {
            $bookId = $this->books->create($userId, $title, $description);
            $this->books->setTags($bookId, $this->tags->idsForNames($userId, $tagNames));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e; // index.php returns 500
        }
        return $this->books->find($bookId);  // Book object
    }

    /** @return array{books: Book[], total: int} */
    public function list(int $userId, int $page, int $limit, ?string $tag): array
    {
        $offset = ($page - 1) * $limit;

        return [
            'books' => $this->books->listForUser($userId, $limit, $offset, $tag),
            'total' => $this->books->countForUser($userId, $tag),
        ];
    }
    /** @param string[] $tagNames */
    public function update(int $userId, int $bookId, string $title, ?string $description, array $tagNames): Book
    {
        $this->findOwned($userId, $bookId);

        $this->pdo->beginTransaction();
        try {
            $this->books->update($bookId, $title, $description);
            $this->books->setTags($bookId, $this->tags->idsForNames($userId, $tagNames));
            $book = $this->books->find($bookId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $book;
    }

    public function changeStatus(int $userId, int $bookId, string $status): Book
    {
        $this->findOwned($userId, $bookId);
        $this->books->updateStatus($bookId, $status);

        return $this->books->find($bookId);
    }

    /** Loads a book and makes sure it belongs to the user: 404 if missing, 403 if someone else's. */
    private function findOwned(int $userId, int $bookId): Book
    {
        $book = $this->books->find($bookId);

        if ($book === null) {
            throw new HttpException(404, 'Book not found');
        }
        if ($book->userId !== $userId) {
            throw new HttpException(403, 'Forbidden');
        }

        return $book;
    }
}
