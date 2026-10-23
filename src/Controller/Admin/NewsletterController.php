<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NewsletterSubscriber;
use App\Pagination\Paginator;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/newsletter')]
final class NewsletterController extends AbstractController
{
    #[Route('', name: 'admin_newsletter_index', methods: ['GET'])]
    public function index(Request $request, NewsletterSubscriberRepository $subscribers): Response
    {
        $search = $request->query->getString('q');

        return $this->render('admin/newsletter/index.html.twig', [
            'subscribers' => Paginator::fromRequestPage($subscribers->createSearchQueryBuilder($search), $request->query->get('page'), 50),
            'search' => $search,
        ]);
    }

    /**
     * CSV export (Excel friendly: UTF-8 BOM and semicolons) to import in a mailing tool.
     */
    #[Route('/export.csv', name: 'admin_newsletter_export', methods: ['GET'])]
    public function export(NewsletterSubscriberRepository $subscribers): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($subscribers): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Email', 'Nom', 'Ville', 'Code postal', 'Origine', 'Date'], ';', escape: '');
            foreach ($subscribers->streamAll() as $subscriber) {
                \assert($subscriber instanceof NewsletterSubscriber);
                fputcsv($output, [
                    $subscriber->getEmail(),
                    $subscriber->getName(),
                    $subscriber->getCity(),
                    $subscriber->getPostalCode(),
                    $subscriber->getSource(),
                    $subscriber->getCreatedAt()->format('d/m/Y'),
                ], ';', escape: '');
            }
            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            \sprintf('newsletter-%s.csv', date('Y-m-d')),
        ));

        return $response;
    }

    #[Route('/{id}/supprimer', name: 'admin_newsletter_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, NewsletterSubscriber $subscriber, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-subscriber-'.$subscriber->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($subscriber);
            $entityManager->flush();
            $this->addFlash('success', \sprintf('%s a été désinscrit de la newsletter.', $subscriber->getEmail()));
        }

        return $this->redirectToRoute('admin_newsletter_index', status: Response::HTTP_SEE_OTHER);
    }
}
