<?php

declare(strict_types=1);

namespace App\Repository;

use App\Http\HttpException;
use App\Model\Book;

final class BookRepository {
    public function __construct(private \PDO $pdo) {
    }

    public function create(int $userId, string $title, ?string $description): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (user_id, title, description) VALUES (?, ?, ?)'
        );
        try {
            $stmt->execute([$userId, $title, $description]);
        } catch (\PDOException $e) {
            $this->handleDuplicateTitle($e);
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

    public function find(int $id): ?Book
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, title, description, status FROM books WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        return $this->toBook($row, $this->tagNamesForBooks([$id])[$id] ?? []);
    }

    /**
     * Loads the tags of many books in one query.
     * @param int[] $bookIds
     * @return array<int, string[]> book id => tag names
     */
    private function tagNamesForBooks(array $bookIds): array
    {
        if ($bookIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT bt.book_id, t.name FROM book_tag bt
             JOIN tags t ON t.id = bt.tag_id
             WHERE bt.book_id IN ($placeholders)
             ORDER BY t.name"
        );
        $stmt->execute(array_values($bookIds));

        $tags = [];
        foreach ($stmt->fetchAll() as $row) {
            $tags[(int)$row['book_id']][] = $row['name'];
        }

        return $tags;
    }

    /** toBook() as a separate method to reuse instead of directly used in find() */
    private function toBook(array $row, array $tags): Book
    {
        return new Book(
            (int)$row['id'],
            (int)$row['user_id'],
            $row['title'],
            $row['description'],
            $row['status'],
            $tags,
        );
    }

    /** @return Book[] */
    public function listForUser(int $userId, int $limit, int $offset, ?string $tag): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.user_id, b.title, b.description, b.status FROM books b'
            . $this->listFilter($tag)
            . ' ORDER BY b.id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        if ($tag !== null) {
            $stmt->bindValue('tag', $tag);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $tags = $this->tagNamesForBooks(array_column($rows, 'id'));

        return array_map(fn (array $row) => $this->toBook($row, $tags[$row['id']] ?? []), $rows);
    }

    public function countForUser(int $userId, ?string $tag): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM books b' . $this->listFilter($tag));
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        if ($tag !== null) {
            $stmt->bindValue('tag', $tag);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /** WHERE clause shared by list and count, so both always use the same filter. */
    private function listFilter(?string $tag): string
    {
        $sql = ' WHERE b.user_id = :user_id';
        if ($tag !== null) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM book_tag bt
                JOIN tags t ON t.id = bt.tag_id
                WHERE bt.book_id = b.id AND t.name = :tag
            )';
        }

        return $sql;
    }

    public function update(int $id, string $title, ?string $description): void
    {
        $stmt = $this->pdo->prepare('UPDATE books SET title = ?, description = ? WHERE id = ?');
        try {
            $stmt->execute([$title, $description, $id]);
        } catch (\PDOException $e) {
            $this->handleDuplicateTitle($e);
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE books SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /** Turns a UNIQUE (user_id, title) violation into a 400, rethrows anything else. */
    private function handleDuplicateTitle(\PDOException $e): never
    {
        if (($e->errorInfo[1] ?? null) === 1062) {
            throw new HttpException(400, 'You already have a book with this title');
        }
        throw $e;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$id]);
    }
}
