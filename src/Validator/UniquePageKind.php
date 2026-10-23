<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Functional pages (home, quote, contact, party photos) exist only once.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniquePageKind extends Constraint
{
    public string $message = 'Une page de type "{{ kind }}" existe déjà : "{{ title }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
