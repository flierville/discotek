<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Some pages embed a functional block (form, gallery access...) under their editable content.
 */
enum PageKind: string
{
    case Standard = 'standard';
    case Home = 'home';
    case Quote = 'quote';
    case Contact = 'contact';
    case PartyPhotos = 'party_photos';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Page simple',
            self::Home => "Page d'accueil",
            self::Quote => 'Devis en ligne (formulaire)',
            self::Contact => 'Contact (formulaire)',
            self::PartyPhotos => 'Photos de soirées (accès par code)',
        };
    }

    /** Functional pages must exist only once. */
    public function isUnique(): bool
    {
        return self::Standard !== $this;
    }
}
