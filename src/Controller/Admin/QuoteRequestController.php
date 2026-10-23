<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\QuoteRequest;
use App\Pagination\Paginator;
use App\Repository\QuoteRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/devis')]
final class QuoteRequestController extends AbstractController
{
    #[Route('', name: 'admin_quote_index', methods: ['GET'])]
    public function index(Request $request, QuoteRequestRepository $quotes): Response
    {
        $search = $request->query->getString('q');

        return $this->render('admin/quote/index.html.twig', [
            'quotes' => Paginator::fromRequestPage($quotes->createSearchQueryBuilder($search), $request->query->get('page')),
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_quote_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(QuoteRequest $quote, EntityManagerInterface $entityManager): Response
    {
        if (!$quote->isRead()) {
            $quote->markAsRead();
            $entityManager->flush();
        }

        return $this->render('admin/quote/show.html.twig', ['quote' => $quote]);
    }

    #[Route('/{id}/supprimer', name: 'admin_quote_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, QuoteRequest $quote, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-quote-'.$quote->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quote);
            $entityManager->flush();
            $this->addFlash('success', 'Le devis a été supprimé.');
        }

        return $this->redirectToRoute('admin_quote_index', status: Response::HTTP_SEE_OTHER);
    }
}
