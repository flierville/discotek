<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Party;
use App\Entity\PartyPhoto;
use App\Form\Admin\PartyFormType;
use App\Pagination\Paginator;
use App\Party\AccessCodeGenerator;
use App\Party\ChunkedUploadReceiver;
use App\Party\PartyManager;
use App\Party\PhotoImporter;
use App\Party\UnsupportedImageException;
use App\Repository\PartyRepository;
use App\Repository\PhotoAccessLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Party photo galleries: the DJ creates the party (title, URL shortcut, code), then drops the
 * photos or a zip archive the day after.
 */
#[Route('/admin/soirees')]
final class PartyController extends AbstractController
{
    public function __construct(private readonly PartyManager $partyManager)
    {
    }

    #[Route('', name: 'admin_party_index', methods: ['GET'])]
    public function index(Request $request, PartyRepository $parties): Response
    {
        $search = $request->query->getString('q');

        return $this->render('admin/party/index.html.twig', [
            'parties' => Paginator::fromRequestPage($parties->createListQueryBuilder($search), $request->query->get('page'), 30),
            'search' => $search,
        ]);
    }

    #[Route('/nouvelle', name: 'admin_party_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AccessCodeGenerator $codeGenerator): Response
    {
        $party = new Party();
        $party->setAccessCode($codeGenerator->generate());
        $form = $this->createForm(PartyFormType::class, $party);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->partyManager->create($party);
            $this->addFlash('success', 'La soirée a été créée : vous pouvez maintenant ajouter les photos.');

            return $this->redirectToRoute('admin_party_edit', ['id' => $party->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/party/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_party_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Party $party, PhotoAccessLogRepository $accessLogs): Response
    {
        $previousSlug = $party->getSlug();
        $form = $this->createForm(PartyFormType::class, $party);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->partyManager->update($party, $previousSlug);
            $this->addFlash('success', 'La soirée a été enregistrée.');

            return $this->redirectToRoute('admin_party_edit', ['id' => $party->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/party/edit.html.twig', [
            'party' => $party,
            'form' => $form,
            'accessLogs' => $accessLogs->findLatestForParty($party),
            'accessCount' => $accessLogs->countForParty($party),
        ]);
    }

    /**
     * Receives one chunk of a picture or of a zip archive (see assets/controllers/photo_uploader_controller.js).
     */
    #[Route('/{id}/photos', name: 'admin_party_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function upload(
        Request $request,
        Party $party,
        ChunkedUploadReceiver $receiver,
        PhotoImporter $importer,
        LoggerInterface $logger,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('party-photos-'.$party->getId(), $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Jeton de sécurité invalide, rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        $chunk = $request->files->get('chunk');
        $originalName = $request->getPayload()->getString('name');
        if (!$chunk instanceof UploadedFile || '' === $originalName) {
            return $this->json(['error' => 'Fichier manquant.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $path = $receiver->receive($chunk, $request->getPayload()->getString('uploadId'), $request->getPayload()->getInt('index'), $request->getPayload()->getInt('count'));
            if (null === $path) {
                return $this->json(['status' => 'partial']);
            }

            try {
                $result = $importer->import($party, $path, $originalName);
            } finally {
                $receiver->cleanup($path);
            }
        } catch (\InvalidArgumentException|UnsupportedImageException $exception) {
            return $this->json(['error' => 'Fichier non pris en charge : '.$originalName], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            $logger->error('Party photo upload failed: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

            return $this->json(['error' => 'Erreur pendant l\'envoi de '.$originalName], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'status' => 'done',
            'rejected' => $result->rejected,
            'photos' => array_map(fn (PartyPhoto $photo): array => [
                'id' => $photo->getId(),
                'thumb' => $this->generateUrl('app_party_photo', ['slug' => $party->getSlug(), 'size' => 'thumb', 'filename' => $photo->getFilename()]),
                'large' => $this->generateUrl('app_party_photo', ['slug' => $party->getSlug(), 'size' => 'large', 'filename' => $photo->getFilename()]),
                'name' => $photo->getOriginalName(),
                'deleteUrl' => $this->generateUrl('admin_party_photo_delete', ['id' => $photo->getId()]),
            ], $result->added),
        ]);
    }

    #[Route('/photos/{id}/supprimer', name: 'admin_party_photo_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePhoto(Request $request, PartyPhoto $photo): Response
    {
        $party = $photo->getParty();
        $valid = $this->isCsrfTokenValid('party-photos-'.$party->getId(), $request->getPayload()->getString('_token'));
        if ($valid) {
            $this->partyManager->deletePhoto($photo);
        }

        if ('json' === $request->getPreferredFormat()) {
            return $this->json(['deleted' => $valid], $valid ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
        }

        $this->addFlash($valid ? 'success' : 'error', $valid ? 'La photo a été supprimée.' : 'Jeton de sécurité invalide.');

        return $this->redirectToRoute('admin_party_edit', ['id' => $party->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/supprimer', name: 'admin_party_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Party $party): Response
    {
        if ($this->isCsrfTokenValid('delete-party-'.$party->getId(), $request->getPayload()->getString('_token'))) {
            $count = $party->getPhotos()->count();
            $this->partyManager->delete($party);
            $this->addFlash('success', \sprintf('La soirée "%s" et ses %d photos ont été supprimées.', $party->getTitle(), $count));
        }

        return $this->redirectToRoute('admin_party_index', status: Response::HTTP_SEE_OTHER);
    }
}
