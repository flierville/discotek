<?php

declare(strict_types=1);

namespace App\Contact;

use App\Entity\ContactMessage;
use App\Form\Model\ContactData;
use App\Mailer\SiteMailer;
use App\Newsletter\NewsletterSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Logs the contact request then emails it to the configured recipients.
 */
final readonly class ContactHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NewsletterSubscription $newsletter,
        private SiteMailer $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function handle(ContactData $data, ?string $ipAddress): ContactMessage
    {
        $phone = trim((string) $data->phone);
        $message = new ContactMessage(
            name: trim((string) $data->name),
            email: mb_strtolower(trim((string) $data->email)),
            phone: '' === $phone ? null : $phone,
            message: trim((string) $data->message),
            newsletter: $data->newsletter,
            ipAddress: $ipAddress,
        );
        $this->entityManager->persist($message);

        if ($data->newsletter) {
            $this->newsletter->subscribe($message->getEmail(), NewsletterSubscription::SOURCE_CONTACT, $message->getName());
        }

        $this->entityManager->flush();
        $this->mailer->sendContact($message);

        return $message;
    }
}
