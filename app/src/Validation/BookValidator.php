<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\HttpException;

final class BookValidator
{
    private const MAX_TAGS = 20;

    public static function create(array $data): array
    {
        /** get data from array and assign null if not existing */
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $tags = $data['tags'] ?? [];

        /** title is string? or empty title? or its length > 255*/
        if (!is_string($title) || trim($title) === '' || mb_strlen(trim($title)) > 255) {
            throw new HttpException(400, 'title is required (max 255 characters)');
        }
        if ($description !== null && (!is_string($description) || mb_strlen($description) > 2000)) {
            throw new HttpException(400, 'description must be a string (max 2000 characters)');
        }
        if (!is_array($tags) || count($tags) > self::MAX_TAGS) {
            throw new HttpException(400, 'tags must be an array of up to 20 strings');
        }

        /** verify each tag in tags[] */
        $cleanTags = [];
        foreach ($tags as $tag) {
            if (!is_string($tag) || trim($tag) === '' || mb_strlen(trim($tag)) > 100) {
                throw new HttpException(400, 'each tag must be a non-empty string (max 100 characters)');
            }
            $cleanTags[] = mb_strtolower(trim($tag));
        }

        return [
            'title' => trim($title),
            'description' => $description,
            'tags' => array_values(array_unique($cleanTags)),
        ];
    }

    private const MAX_LIMIT = 100;

    public static function listQuery(array $query): array
    {
        $page = self::positiveInt($query['page'] ?? '1', 'page');
        $limit = self::positiveInt($query['limit'] ?? '10', 'limit');
        if ($limit > self::MAX_LIMIT) {
            throw new HttpException(400, 'limit must not be greater than 100');
        }

        $tag = $query['tag'] ?? null;
        if ($tag !== null && (!is_string($tag) || trim($tag) === '')) {
            throw new HttpException(400, 'tag must be a non-empty string');
        }

        return [
            'page' => $page,
            'limit' => $limit,
            'tag' => $tag === null ? null : mb_strtolower(trim($tag)),
        ];
    }

    private static function positiveInt(mixed $value, string $name): int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($int === false) {
            throw new HttpException(400, "$name must be a positive integer");
        }
        return $int;
    }
}


