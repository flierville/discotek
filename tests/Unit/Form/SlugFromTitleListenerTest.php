<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\EventListener\SlugFromTitleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

final class SlugFromTitleListenerTest extends TestCase
{
    public function testEmptySlugIsBuiltFromTheTitle(): void
    {
        $event = new FormEvent($this->createStub(FormInterface::class), ['title' => "Mariage d'Hélène & Noël", 'slug' => '']);

        (new SlugFromTitleListener())->onPreSubmit($event);

        self::assertSame('mariage-d-helene-noel', $event->getData()['slug']);
    }

    public function testSlugTypedByTheUserIsKept(): void
    {
        $event = new FormEvent($this->createStub(FormInterface::class), ['title' => 'Mariage', 'slug' => 'mon-raccourci']);

        (new SlugFromTitleListener())->onPreSubmit($event);

        self::assertSame('mon-raccourci', $event->getData()['slug']);
    }
}
