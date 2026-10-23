<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Entity\ContactMessage;
use App\Entity\QuoteRequest;
use App\Settings\SettingsProvider;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Every email sent by the public site. Notifications to the DJ use the visitor as Reply-To
 * (never as From, which would break SPF/DKIM).
 */
final readonly class SiteMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private SettingsProvider $settingsProvider,
        #[Autowire('%env(APP_MAIL_FROM_ADDRESS)%')]
        private string $fromAddress,
    ) {
    }

    /**
     * @throws TransportExceptionInterface when the confirmation to the visitor cannot be sent
     */
    public function sendQuote(QuoteRequest $quote): void
    {
        $settings = $this->settingsProvider->get();
        $context = ['quote' => $quote, 'settings' => $settings];

        $this->notifyTeam(
            $settings->getQuoteRecipients(),
            new Address($quote->getEmail(), $quote->getFullName()),
            'Demande de Tarif',
            'emails/quote_admin.html.twig',
            $context,
        );

        $this->mailer->send(
            $this->createEmail()
                ->to(new Address($quote->getEmail(), $quote->getFullName()))
                ->subject(\sprintf('Confirmation de demande de tarif sur le site %s', $settings->getSiteName()))
                ->htmlTemplate('emails/quote_customer.html.twig')
                ->context($context),
        );
    }

    /**
     * @throws TransportExceptionInterface when the confirmation to the visitor cannot be sent
     */
    public function sendContact(ContactMessage $message): void
    {
        $settings = $this->settingsProvider->get();
        $context = ['message' => $message, 'settings' => $settings];

        $this->notifyTeam(
            $settings->getContactRecipients(),
            new Address($message->getEmail(), $message->getName()),
            \sprintf('Contact provenant du site %s', $settings->getSiteName()),
            'emails/contact_admin.html.twig',
            $context,
        );

        $this->mailer->send(
            $this->createEmail()
                ->to(new Address($message->getEmail(), $message->getName()))
                ->subject(\sprintf('Confirmation de Message sur le site %s', $settings->getSiteName()))
                ->htmlTemplate('emails/contact_customer.html.twig')
                ->context($context),
        );
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    private function notifyTeam(array $recipients, Address $replyTo, string $subject, string $template, array $context): void
    {
        $recipients = [] !== $recipients ? $recipients : [$this->settingsProvider->get()->getPublicEmail()];

        $this->mailer->send(
            $this->createEmail()
                ->to(...$recipients)
                ->replyTo($replyTo)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context),
        );
    }

    private function createEmail(): TemplatedEmail
    {
        return (new TemplatedEmail())->from(new Address($this->fromAddress, $this->settingsProvider->get()->getSiteName()));
    }
}
