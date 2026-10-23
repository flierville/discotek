<?php

declare(strict_types=1);

namespace App\Tests\Unit\Party;

use App\Entity\Party;
use App\Party\AccessCodeGenerator;
use App\Repository\PartyRepository;
use PHPUnit\Framework\TestCase;

final class AccessCodeGeneratorTest extends TestCase
{
    public function testCodeIsEasyToDictate(): void
    {
        $parties = $this->createStub(PartyRepository::class);
        $parties->method('findOneBy')->willReturn(null);

        $code = (new AccessCodeGenerator($parties))->generate();

        self::assertMatchesRegularExpression('/^['.AccessCodeGenerator::ALPHABET.']{6}$/', $code);
        self::assertDoesNotMatchRegularExpression('/[01ilo]/', $code);
    }

    public function testCodeAlreadyUsedIsNeverReturned(): void
    {
        $parties = $this->createMock(PartyRepository::class);
        $parties->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(new Party(), null);

        self::assertSame(4, \strlen((new AccessCodeGenerator($parties, 4))->generate()));
    }
}
