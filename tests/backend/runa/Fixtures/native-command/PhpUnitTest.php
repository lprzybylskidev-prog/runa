<?php

namespace Tests\Fixtures\NativeCommand;

use PHPUnit\Framework\TestCase;

final class PhpUnitTest extends TestCase
{
    public function test_executes_a_phpunit_class_through_the_native_command(): void
    {
        self::assertSame('testing', getenv('APP_ENV'));
    }
}
