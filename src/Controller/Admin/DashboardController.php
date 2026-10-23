<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ContactMessageRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PartyRepository;
use App\Repository\QuoteRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        QuoteRequestRepository $quotes,
        ContactMessageRepository $contacts,
        PartyRepository $parties,
        NewsletterSubscriberRepository $subscribers,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'quotesLast30Days' => $quotes->countSince(new \DateTimeImmutable('-30 days')),
                'unreadQuotes' => $quotes->countUnread(),
                'unreadContacts' => $contacts->countUnread(),
                'parties' => $parties->count([]),
                'photos' => $parties->countPhotos(),
                'subscribers' => $subscribers->count([]),
            ],
            'latestQuotes' => $quotes->findLatest(6),
            'latestContacts' => $contacts->findLatest(6),
        ]);
    }
}
