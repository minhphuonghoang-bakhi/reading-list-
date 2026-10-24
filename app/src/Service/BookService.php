<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Book;
use App\Repository\BookRepository;
use App\Repository\TagRepository;

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
}
