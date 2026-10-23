<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\DataTransformer\EmailListTransformer;
use PHPUnit\Framework\TestCase;

final class EmailListTransformerTest extends TestCase
{
    public function testListIsDisplayedOneEmailPerLine(): void
    {
        self::assertSame("a@example.com\nb@example.com", (new EmailListTransformer())->transform(['a@example.com', 'b@example.com']));
    }

    public function testLinesCommasAndSemicolonsAreAccepted(): void
    {
        $emails = (new EmailListTransformer())->reverseTransform(" A@example.com\r\nb@example.com, c@example.com;\n\na@example.com ");

        self::assertSame(['a@example.com', 'b@example.com', 'c@example.com'], $emails);
    }
}
