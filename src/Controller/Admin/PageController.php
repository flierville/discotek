<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Form\Admin\PageFormType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/pages')]
final class PageController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_page_index', methods: ['GET'])]
    public function index(PageRepository $pages): Response
    {
        return $this->render('admin/page/index.html.twig', ['pages' => $pages->findAllOrdered()]);
    }

    #[Route('/nouvelle', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Page(), 'La page a été créée.');
    }

    #[Route('/{id}', name: 'admin_page_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Page $page): Response
    {
        return $this->handleForm($request, $page, 'La page a été enregistrée.');
    }

    #[Route('/{id}/supprimer', name: 'admin_page_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Page $page): Response
    {
        if ($page->getKind()->isUnique()) {
            $this->addFlash('error', 'Cette page affiche un formulaire ou est la page d\'accueil : vous pouvez la mettre hors ligne mais pas la supprimer.');
        } elseif ($this->isCsrfTokenValid('delete-page-'.$page->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($page);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('La page "%s" a été supprimée.', $page->getTitle()));
        }

        return $this->redirectToRoute('admin_page_index', status: Response::HTTP_SEE_OTHER);
    }

    private function handleForm(Request $request, Page $page, string $successMessage): Response
    {
        $form = $this->createForm(PageFormType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($page);
            $this->entityManager->flush();
            $this->addFlash('success', $successMessage);

            return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/page/form.html.twig', ['page' => $page, 'form' => $form]);
    }
}
