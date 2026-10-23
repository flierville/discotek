<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Page;
use App\Form\Front\QuoteRequestType;
use App\Form\Model\QuoteRequestData;
use App\Quote\QuoteRequestHandler;
use App\Security\PublicFormThrottle;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Online quote form, displayed under the content of the "quote" page (see PageController).
 */
final class QuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRequestHandler $handler,
        private readonly PublicFormThrottle $throttle,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function form(Page $page, Request $request): Response
    {
        $form = $this->createForm(QuoteRequestType::class, new QuoteRequestData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->throttle->allows($request)) {
                $form->addError(new FormError('Trop de demandes ont été envoyées depuis votre connexion, merci de réessayer plus tard.'));
            } else {
                try {
                    $this->handler->handle($form->getData(), $request->getClientIp());
                    $this->addFlash('information', 'quote_sent');

                    return $this->redirectToRoute('app_information');
                } catch (TransportExceptionInterface $exception) {
                    $this->logger->error('Quote email could not be sent: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);
                    $form->addError(new FormError("Une erreur technique s'est produite lors de l'envoi du mail. Veuillez réessayer. Si le problème persiste, merci de nous contacter soit par mail soit par téléphone."));
                }
            }
        }

        return $this->render('front/page/quote.html.twig', ['page' => $page, 'form' => $form]);
    }
}
