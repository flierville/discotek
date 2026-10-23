<?php

declare(strict_types=1);

namespace App\Party;

use App\Entity\Party;
use App\Entity\PartyPhoto;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Keeps the database and the photo directories in sync.
 */
final readonly class PartyManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PartyStorage $storage,
    ) {
    }

    public function create(Party $party): void
    {
        $this->entityManager->persist($party);
        $this->entityManager->flush();
        $this->storage->prepare($party->getSlug());
    }

    /**
     * @param string $previousSlug slug before the edition, the directory follows a slug change
     */
    public function update(Party $party, string $previousSlug): void
    {
        $this->storage->rename($previousSlug, $party->getSlug());

        try {
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->storage->rename($party->getSlug(), $previousSlug);

            throw $exception;
        }
    }

    /**
     * Deleting a party deletes its photos.
     */
    public function delete(Party $party): void
    {
        $slug = $party->getSlug();
        $this->entityManager->remove($party);
        $this->entityManager->flush();
        $this->storage->removeDirectory($slug);
    }

    public function deletePhoto(PartyPhoto $photo): void
    {
        $photo->getParty()->removePhoto($photo);
        $this->entityManager->flush();
        $this->storage->removePhoto($photo);
    }
}
