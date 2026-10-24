<?php

declare(strict_types=1);

namespace App\Model;
final class Book {
    /** @param string[] $tags */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly array $tags,) {
    }

/** Data fields returned to the client (userId stays internal). */
    public function toArray(): array {
    return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'status' => $this->status,
        'tags' => $this->tags,
    ];
    }
}

