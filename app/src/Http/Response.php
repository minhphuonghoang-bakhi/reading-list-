<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    private function __construct(
        public readonly int    $status,
        public readonly ?array $body,
    ) {
    }

    public static function json(array $body, int $status = 200): self
    {
        return new self($status, $body);
    }

    /**
    * Response has empty body
    */
    public static function noContent(): self
    {
        return new self(204, null);
    }

    public function send(): void
    {
        http_response_code($this->status);
        if ($this->body === null) {
            return;
        }
        header('Content-Type: application/json');
        echo json_encode($this->body, JSON_THROW_ON_ERROR);
    }
}