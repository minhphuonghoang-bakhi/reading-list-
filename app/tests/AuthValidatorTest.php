<?php

declare(strict_types=1);

namespace Tests;

use App\Http\HttpException;
use App\Validation\AuthValidator;
use PHPUnit\Framework\TestCase;

final class AuthValidatorTest extends TestCase
{
    public function testRegisterCleansNameAndLowercasesEmail(): void
    {
        $data = AuthValidator::register([
            'name' => '  John Doe ',
            'email' => 'John@Example.com',
            'password' => 'password123',
        ]);

        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
        $this->assertSame('password123', $data['password']);
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->assertBadRequest(fn () => AuthValidator::register([
            'name' => 'John', 'email' => 'not-an-email', 'password' => 'password123',
        ]));
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $this->assertBadRequest(fn () => AuthValidator::register([
            'name' => 'John', 'email' => 'john@example.com', 'password' => 'short',
        ]));
    }

    public function testRegisterRejectsPasswordLongerThanBcryptLimit(): void
    {
        $this->assertBadRequest(fn () => AuthValidator::register([
            'name' => 'John', 'email' => 'john@example.com', 'password' => str_repeat('a', 73),
        ]));
    }

    public function testRegisterRejectsNonStringValues(): void
    {
        $this->assertBadRequest(fn () => AuthValidator::register([
            'name' => 'John', 'email' => 'john@example.com', 'password' => 12345678,
        ]));
    }

    public function testLoginRequiresEmailAndPassword(): void
    {
        $this->assertBadRequest(fn () => AuthValidator::login(['email' => 'john@example.com']));
        $this->assertBadRequest(fn () => AuthValidator::login([]));
    }

    public function testLoginLowercasesEmail(): void
    {
        $data = AuthValidator::login(['email' => 'John@Example.com', 'password' => 'x']);

        $this->assertSame('john@example.com', $data['email']);
    }

    private function assertBadRequest(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected HttpException 400');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->status);
        }
    }
}
