<?php

declare(strict_types=1);

namespace App\Party;

use App\Entity\PartyPhoto;

final class ImportResult
{
    /** @var list<PartyPhoto> */
    public array $added = [];

    /** @var list<string> names of the files which are not supported pictures */
    public array $rejected = [];
}
