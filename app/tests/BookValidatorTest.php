<?php

declare(strict_types=1);

namespace Tests;

use App\Http\HttpException;
use App\Validation\BookValidator;
use PHPUnit\Framework\TestCase;

final class BookValidatorTest extends TestCase
{
    public function testCreateCleansTitleAndTags(): void
    {
        $data = BookValidator::create([
            'title' => '  Clean Code ',
            'tags' => ['Tech', 'tech', ' coding '],
        ]);

        $this->assertSame('Clean Code', $data['title']);
        $this->assertNull($data['description']);
        $this->assertSame(['tech', 'coding'], $data['tags']);
    }

    public function testCreateRejectsEmptyTitle(): void
    {
        $this->assertBadRequest(fn () => BookValidator::create(['title' => '   ']));
    }

    public function testCreateRejectsTagsThatAreNotAnArray(): void
    {
        $this->assertBadRequest(fn () => BookValidator::create(['title' => 'A', 'tags' => 'tech']));
    }

    public function testStatusAcceptsOnlyKnownValues(): void
    {
        $this->assertSame('reading', BookValidator::status(['status' => 'reading']));
        $this->assertBadRequest(fn () => BookValidator::status(['status' => 'finished']));
    }

    public function testListQueryUsesDefaultsAndCapsLimit(): void
    {
        $this->assertSame(['page' => 1, 'limit' => 10, 'tag' => null], BookValidator::listQuery([]));
        $this->assertBadRequest(fn () => BookValidator::listQuery(['limit' => '1000']));
        $this->assertBadRequest(fn () => BookValidator::listQuery(['page' => 'abc']));
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