<?php

declare(strict_types=1);

namespace App\Party;

use App\Entity\Party;
use App\Entity\PhotoAccessLog;
use App\Form\Model\PhotoAccessData;
use App\Newsletter\NewsletterSubscription;
use App\Repository\PartyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Grants a visitor access to a party gallery once they gave a valid party code.
 * Granted galleries are remembered in the session.
 */
final readonly class GalleryAccess
{
    private const SESSION_KEY = 'party_gallery_access';

    public function __construct(
        private PartyRepository $parties,
        private EntityManagerInterface $entityManager,
        private NewsletterSubscription $newsletter,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return Party|null null when no published party matches the code
     */
    public function requestAccess(PhotoAccessData $data, ?string $ipAddress): ?Party
    {
        $party = $this->parties->findPublishedByCode((string) $data->code);
        if (null === $party) {
            return null;
        }

        \assert(null !== $data->location);
        $email = mb_strtolower(trim((string) $data->email));
        $this->entityManager->persist(new PhotoAccessLog(
            party: $party,
            name: trim((string) $data->name),
            email: $email,
            city: $data->location->city,
            postalCode: $data->location->postalCode,
            newsletter: $data->newsletter,
            ipAddress: $ipAddress,
        ));

        if ($data->newsletter) {
            $this->newsletter->subscribe($email, NewsletterSubscription::SOURCE_PHOTOS, trim((string) $data->name), $data->location->city, $data->location->postalCode);
        }

        $this->entityManager->flush();
        $this->grant($party);

        return $party;
    }

    public function grant(Party $party): void
    {
        $session = $this->requestStack->getSession();
        $granted = $session->get(self::SESSION_KEY, []);
        $granted[$party->getId()] = true;
        $session->set(self::SESSION_KEY, $granted);
    }

    public function isGranted(Party $party): bool
    {
        $granted = $this->requestStack->getSession()->get(self::SESSION_KEY, []);

        return isset($granted[$party->getId()]);
    }
}
