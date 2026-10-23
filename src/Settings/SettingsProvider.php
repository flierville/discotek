<?php

declare(strict_types=1);

namespace App\Settings;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Gives access to the single Settings row (memoized for the request).
 */
class SettingsProvider implements ResetInterface
{
    private ?Settings $settings = null;

    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function get(): Settings
    {
        return $this->settings ??= $this->repository->findOneBy([], ['id' => 'ASC']) ?? new Settings();
    }

    public function save(Settings $settings): void
    {
        $this->entityManager->persist($settings);
        $this->entityManager->flush();
        $this->settings = $settings;
    }

    public function reset(): void
    {
        $this->settings = null;
    }
}
