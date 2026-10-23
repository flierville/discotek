<?php

declare(strict_types=1);

namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Fills an empty slug field from the title field before validation.
 */
final readonly class SlugFromTitleListener implements EventSubscriberInterface
{
    public function __construct(private string $titleField = 'title', private string $slugField = 'slug')
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SUBMIT => 'onPreSubmit'];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!\is_array($data) || '' !== trim((string) ($data[$this->slugField] ?? ''))) {
            return;
        }

        $title = trim((string) ($data[$this->titleField] ?? ''));
        $data[$this->slugField] = (new AsciiSlugger('fr'))->slug($title)->lower()->toString();
        $event->setData($data);
    }
}
