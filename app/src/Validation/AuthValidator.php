<?php


declare(strict_types=1);

namespace App\Validation;

use App\Http\HttpException;

final class AuthValidator
{
    /** Extract fields from the input array for validation, then return them as a normalized array. */
    public static function register(array $data): array
    {
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 100) {
            throw new HttpException(400, 'name is required (max 100 characters)');
        }
        if (!is_string($email) || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException(400, 'a valid email is required');
        }
        // bcrypt only uses the first 72 bytes, so cap it
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new HttpException(400, 'password must be 8 to 72 characters');
        }

        return [
            'name' => trim($name),
            'email' => strtolower($email),
            'password' => $password,
        ];
    }
}
