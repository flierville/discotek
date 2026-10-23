<?php

declare(strict_types=1);

namespace App\Tests\Unit\Legacy;

use App\Legacy\LegacyContentCleaner;
use PHPUnit\Framework\TestCase;

final class LegacyContentCleanerTest extends TestCase
{
    private LegacyContentCleaner $cleaner;

    protected function setUp(): void
    {
        $this->cleaner = new LegacyContentCleaner();
    }

    public function testFormPlaceholdersAreRemoved(): void
    {
        $html = '<h1>Tarifs en ligne</h1><div>texte</div><br />{FORM_DEVIS}';

        self::assertSame('<h1>Tarifs en ligne</h1><div>texte</div>', $this->cleaner->cleanPageContent($html));
    }

    public function testYoutubeVideosUseHttps(): void
    {
        $html = '<iframe src="http://www.youtube.com/embed/5sUO67AADyg"></iframe>';

        self::assertSame('<iframe src="https://www.youtube.com/embed/5sUO67AADyg"></iframe>', $this->cleaner->cleanPageContent($html));
    }

    public function testDoubleEncodedTextIsRepaired(): void
    {
        self::assertSame('Anniversaires de Fredérique et Anthony', $this->cleaner->fixDoubleEncoding('Anniversaires de FredÃ©rique et Anthony'));
        self::assertSame('Mariage de Stéphanie', $this->cleaner->fixDoubleEncoding('Mariage de StÃ©phanie'));
    }

    public function testCorrectTextIsKept(): void
    {
        self::assertSame('Baptême de Maëlle', $this->cleaner->fixDoubleEncoding('Baptême de Maëlle'));
    }

    public function testTitleIsDecodedAndTrimmed(): void
    {
        self::assertSame("Noces d'or de Paulette", $this->cleaner->cleanTitle("  Noces d&#039;or   de Paulette "));
    }

    public function testPartyCodeIsTheDirectoryOfThePhotos(): void
    {
        self::assertSame('lila20', $this->cleaner->partyCodeFromMedia('/medias/images/photos_soirees/lila20/20120716_165526.jpg:0:0:::;'));
        self::assertNull($this->cleaner->partyCodeFromMedia('/medias/images/p1050041.jpg:0:0:::;'));
    }
}
