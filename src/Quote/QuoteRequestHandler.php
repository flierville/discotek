<?php

declare(strict_types=1);

namespace App\Quote;

use App\Entity\QuoteRequest;
use App\Form\Model\QuoteRequestData;
use App\Mailer\SiteMailer;
use App\Newsletter\NewsletterSubscription;
use App\Settings\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Prices, stores and emails an online quote request.
 */
final readonly class QuoteRequestHandler
{
    public function __construct(
        private QuoteCalculator $calculator,
        private SettingsProvider $settingsProvider,
        private NewsletterSubscription $newsletter,
        private EntityManagerInterface $entityManager,
        private SiteMailer $mailer,
    ) {
    }

    /**
     * The request is stored before sending the emails, so it is never lost.
     *
     * @throws TransportExceptionInterface
     */
    public function handle(QuoteRequestData $data, ?string $ipAddress): QuoteRequest
    {
        \assert(null !== $data->guestRange && null !== $data->location && null !== $data->eventDate);

        $price = $this->calculator->calculate(
            TravelPolicy::fromSettings($this->settingsProvider->get()),
            $data->guestRange,
            $data->eventType,
            $data->eventDate,
            $data->location->point,
        );

        $quote = new QuoteRequest(
            lastName: trim((string) $data->lastName),
            firstName: self::nullIfBlank($data->firstName),
            email: mb_strtolower(trim((string) $data->email)),
            phone: trim((string) $data->phone),
            postalAddress: self::nullIfBlank($data->postalAddress),
            eventType: $data->eventType?->getLabel(),
            guestRange: $data->guestRange->getLabel(),
            city: $data->location->city,
            postalCode: $data->location->postalCode,
            eventDate: $data->eventDate,
            details: self::nullIfBlank($data->details),
            newsletter: $data->newsletter,
            ipAddress: $ipAddress,
            distanceKm: $price->distanceKm,
            servicePrice: $price->servicePrice,
            travelPrice: $price->travelPrice,
            pricingRule: $price->pricingRule,
        );
        $this->entityManager->persist($quote);

        if ($data->newsletter) {
            $this->newsletter->subscribe($quote->getEmail(), NewsletterSubscription::SOURCE_QUOTE, $quote->getFullName(), $quote->getCity(), $quote->getPostalCode());
        }

        $this->entityManager->flush();
        $this->mailer->sendQuote($quote);

        return $quote;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
