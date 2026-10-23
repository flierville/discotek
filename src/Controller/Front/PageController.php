<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Enum\PageKind;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    public function __construct(private readonly PageRepository $pages)
    {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        $page = $this->pages->findPublishedByKind(PageKind::Home) ?? throw new NotFoundHttpException('No home page.');

        return $this->render('front/page/show.html.twig', ['page' => $page]);
    }

    /**
     * Catch-all route of the editable pages: must stay the last route.
     */
    #[Route('/{slug}', name: 'app_page', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'], methods: ['GET', 'POST'], priority: -100)]
    public function show(string $slug): Response
    {
        $page = $this->pages->findPublishedBySlug($slug) ?? throw $this->createNotFoundException();

        return match ($page->getKind()) {
            PageKind::Home => $this->redirectToRoute('app_home', status: Response::HTTP_MOVED_PERMANENTLY),
            PageKind::Quote => $this->forward(QuoteController::class.'::form', ['page' => $page]),
            PageKind::Contact => $this->forward(ContactController::class.'::form', ['page' => $page]),
            PageKind::PartyPhotos => $this->forward(PartyGalleryController::class.'::accessForm', ['page' => $page]),
            PageKind::Standard => $this->render('front/page/show.html.twig', ['page' => $page]),
        };
    }

    /**
     * Confirmation displayed after a form submission (legacy "Information" page).
     */
    #[Route('/information', name: 'app_information', methods: ['GET'], priority: 10)]
    public function information(Request $request): Response
    {
        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();
        $messages = $session->getFlashBag()->get('information');
        if ([] === $messages) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('front/page/information.html.twig', ['messages' => $messages]);
    }
}
