<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Pagination\Paginator;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contacts')]
final class ContactMessageController extends AbstractController
{
    #[Route('', name: 'admin_contact_index', methods: ['GET'])]
    public function index(Request $request, ContactMessageRepository $messages): Response
    {
        $search = $request->query->getString('q');

        return $this->render('admin/contact/index.html.twig', [
            'messages' => Paginator::fromRequestPage($messages->createSearchQueryBuilder($search), $request->query->get('page')),
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_contact_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        if (!$message->isRead()) {
            $message->markAsRead();
            $entityManager->flush();
        }

        return $this->render('admin/contact/show.html.twig', ['message' => $message]);
    }

    #[Route('/{id}/supprimer', name: 'admin_contact_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-contact-'.$message->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
            $this->addFlash('success', 'Le message a été supprimé.');
        }

        return $this->redirectToRoute('admin_contact_index', status: Response::HTTP_SEE_OTHER);
    }
}
