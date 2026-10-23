<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * list of emails <-> one email per line (commas and semicolons are accepted too).
 *
 * @implements DataTransformerInterface<list<string>, string>
 */
final class EmailListTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        return implode("\n", (array) $value);
    }

    /** @return list<string> */
    public function reverseTransform(mixed $value): array
    {
        $emails = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map(static fn (string $email): string => mb_strtolower($email), $emails)));
    }
}
