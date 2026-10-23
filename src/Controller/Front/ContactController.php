<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Contact\ContactHandler;
use App\Entity\Page;
use App\Form\Front\ContactType;
use App\Form\Model\ContactData;
use App\Security\PublicFormThrottle;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Contact form, displayed under the content of the "contact" page (see PageController).
 */
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactHandler $handler,
        private readonly PublicFormThrottle $throttle,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function form(Page $page, Request $request): Response
    {
        $form = $this->createForm(ContactType::class, new ContactData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->throttle->allows($request)) {
                $form->addError(new FormError('Trop de messages ont été envoyés depuis votre connexion, merci de réessayer plus tard.'));
            } else {
                try {
                    $this->handler->handle($form->getData(), $request->getClientIp());
                    $this->addFlash('information', 'contact_sent');

                    return $this->redirectToRoute('app_information');
                } catch (TransportExceptionInterface $exception) {
                    $this->logger->error('Contact email could not be sent: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);
                    $form->addError(new FormError("Une erreur technique s'est produite lors de l'envoi du mail. Veuillez réessayer. Si le problème persiste, merci de nous contacter par téléphone."));
                }
            }
        }

        return $this->render('front/page/contact.html.twig', ['page' => $page, 'form' => $form]);
    }
}
