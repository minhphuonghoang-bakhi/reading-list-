<?php

declare(strict_types=1);

namespace App\Repository;

final class TagRepository {
    public function __construct(private \PDO $pdo) {
    }

    /** Convert the array of tag names into tag_id array to assign to book_tags table
     * which only contains id, not the names
     */
    public function idsForNames(int $userId, array $names): array {
        /** if violate the constraint unique, just update name */
        $insert = $this->pdo->prepare(
            'INSERT INTO tags (user_id, name) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE name = name'
        );
        $select = $this->pdo->prepare('SELECT id FROM tags WHERE user_id = ? AND name = ?');

        $ids = [];
        foreach ($names as $name) {
            $insert->execute([$userId, $name]);
            $select->execute([$userId, $name]);
            $ids[] = (int)$select->fetchColumn();
        }
        return $ids;
    }
}
