<?php

declare(strict_types=1);

namespace App\Party;

use App\Repository\PartyRepository;

/**
 * Party codes are dictated during the party: lower case, no ambiguous characters (0/o, 1/l/i).
 */
final readonly class AccessCodeGenerator
{
    public const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(private PartyRepository $parties, private int $length = 6)
    {
    }

    public function generate(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < $this->length; ++$i) {
                $code .= self::ALPHABET[random_int(0, \strlen(self::ALPHABET) - 1)];
            }
        } while (null !== $this->parties->findOneBy(['accessCode' => $code]));

        return $code;
    }
}
