<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        public readonly string  $method,
        public readonly string  $path,
        public readonly array   $query,
        private readonly array  $headers,
        private readonly string $rawBody,
    ) {
    }
    /**
     * Create a new Request instance from PHP global variables.
     * @return static
     */
    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rtrim($path, '/') ?: '/',
            $_GET,
            function_exists('getallheaders') ? getallheaders() : [],
            file_get_contents('php://input') ?: '',
        );
    }

    /** Decoded JSON body into associative array */
    public function json(): array
    {
        if ($this->rawBody === '') {
            return [];
        }
        $data = json_decode($this->rawBody, true);
        if (!is_array($data)) {
            throw new HttpException(400, 'Invalid JSON body');
        }
        return $data;
    }

    /** Token from "Authorization: Bearer <token>", or null.
     * Bearer abc123 -> abc123
     */
    public function bearerToken(): ?string
    {
        foreach ($this->headers as $name => $value) {
            if (strtolower((string)$name) === 'authorization'
                && preg_match('/^Bearer\s+(\S+)$/i', (string)$value, $m)) {
                return $m[1];
            }
        }
        return null;
    }
}
