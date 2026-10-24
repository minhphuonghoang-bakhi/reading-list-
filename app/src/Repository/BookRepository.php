<?php

declare(strict_types=1);

namespace App\Repository;

use App\Http\HttpException;
use App\Model\Book;

final class BookRepository {
    public function __construct(private \PDO $pdo) {
    }
    //CREATE
    public function create(int $userId, string $title, ?string $description): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (user_id, title, description) VALUES (?, ?, ?)'
        );
        try {
            $stmt->execute([$userId, $title, $description]);
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new HttpException(400, 'You already have a book with this title');
            }
            throw $e;
        }
        return (int)$this->pdo->lastInsertId();
    }

    /** If user changes tags for 1 book, delete all old tags and insert new ones */
    public function setTags(int $bookId, array $tagIds): void {
        $this->pdo->prepare('DELETE FROM book_tag WHERE book_id = ?')->execute([$bookId]);

        $stmt = $this->pdo->prepare('INSERT INTO book_tag (book_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([$bookId, $tagId]);
        }
    }

    public function find(int $id): ?Book {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, title, description, status FROM books WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(); //array

        return $row === false ? null : $this->toBook($row);
    }

    /** toBook() as a separate method to reuse instead of directly used in find() */
    private function toBook(array $row): Book
    {
        return new Book(
            (int)$row['id'],
            (int)$row['user_id'],
            $row['title'],
            $row['description'],
            $row['status'],
            $this->tagNames((int)$row['id']),
        );
    }
    /** @return string[] */
    private function tagNames(int $bookId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.name FROM tags t
             JOIN book_tag bt ON bt.tag_id = t.id
             WHERE bt.book_id = ?
             ORDER BY t.name'
        );
        $stmt->execute([$bookId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
