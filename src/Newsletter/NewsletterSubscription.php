<?php

declare(strict_types=1);

namespace App\Newsletter;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Subscribes a visitor who ticked "Inscription à la Newsletter" (idempotent, no flush).
 */
final readonly class NewsletterSubscription
{
    public const SOURCE_QUOTE = 'devis';
    public const SOURCE_CONTACT = 'contact';
    public const SOURCE_PHOTOS = 'photos';
    public const SOURCE_LEGACY = 'ancien site';

    public function __construct(
        private NewsletterSubscriberRepository $subscribers,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function subscribe(string $email, string $source, ?string $name = null, ?string $city = null, ?string $postalCode = null): NewsletterSubscriber
    {
        $subscriber = $this->subscribers->findOneByEmail($email);
        if (null !== $subscriber) {
            return $subscriber;
        }

        $subscriber = (new NewsletterSubscriber($email, $source))
            ->setName($name)
            ->setCity($city)
            ->setPostalCode($postalCode);
        $this->entityManager->persist($subscriber);

        return $subscriber;
    }
}
