<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Page;
use App\Entity\Party;
use App\Entity\User;
use App\Enum\PageKind;
use App\Form\Front\PhotoAccessType;
use App\Form\Model\PhotoAccessData;
use App\Party\GalleryAccess;
use App\Party\PartyStorage;
use App\Repository\PageRepository;
use App\Repository\PartyPhotoRepository;
use App\Security\PublicFormThrottle;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Party photos: the visitor gives the party code, then browses the gallery.
 */
final class PartyGalleryController extends AbstractController
{
    public function __construct(
        private readonly GalleryAccess $galleryAccess,
        private readonly PartyStorage $storage,
        private readonly PageRepository $pages,
    ) {
    }

    /**
     * Access form, displayed under the content of the "party photos" page (see PageController).
     */
    public function accessForm(Page $page, Request $request, PublicFormThrottle $throttle): Response
    {
        $form = $this->createForm(PhotoAccessType::class, new PhotoAccessData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $party = $throttle->allows($request) ? $this->galleryAccess->requestAccess($form->getData(), $request->getClientIp()) : null;

            if (null !== $party) {
                return $this->redirectToRoute('app_party_gallery', ['slug' => $party->getSlug()]);
            }

            $form->get('code')->addError(new FormError('Le code soirée est introuvable, merci de nous contacter pour corriger le problème.'));
        }

        return $this->render('front/page/party_access.html.twig', ['page' => $page, 'form' => $form]);
    }

    #[Route('/photos-de-soirees/{slug}', name: 'app_party_gallery', requirements: ['slug' => Party::SLUG_PATTERN], methods: ['GET'])]
    public function gallery(#[MapEntity(mapping: ['slug' => 'slug'])] Party $party): Response
    {
        if (!$this->canView($party)) {
            $this->addFlash('gallery_error', 'Veuillez saisir le code de la soirée pour accéder aux photos.');

            return $this->redirectToAccessForm();
        }

        return $this->render('front/party/gallery.html.twig', [
            'party' => $party,
            'page' => $this->pages->findPublishedByKind(PageKind::PartyPhotos),
        ]);
    }

    #[Route(
        '/photos-de-soirees/{slug}/{size}/{filename}',
        name: 'app_party_photo',
        requirements: ['slug' => Party::SLUG_PATTERN, 'size' => 'large|thumb', 'filename' => '[a-z0-9_-]+\.jpg'],
        methods: ['GET'],
    )]
    public function photo(
        #[MapEntity(mapping: ['slug' => 'slug'])] Party $party,
        string $size,
        string $filename,
        Request $request,
        PartyPhotoRepository $photos,
    ): Response {
        $photo = $photos->findOneBy(['party' => $party, 'filename' => $filename]);
        if (null === $photo || !$this->canView($party)) {
            throw $this->createNotFoundException();
        }

        $path = $this->storage->photoPath($photo, $size);
        if (!is_file($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path, autoEtag: true, autoLastModified: true);
        $response->setPrivate();
        $response->setMaxAge(86400);

        if ($request->query->getBoolean('download')) {
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $photo->getFilename());
        }

        return $response;
    }

    private function canView(Party $party): bool
    {
        if ($this->getUser() instanceof User) {
            return true; // Administrators preview every gallery.
        }

        return $party->isPublished() && $this->galleryAccess->isGranted($party);
    }

    private function redirectToAccessForm(): Response
    {
        $page = $this->pages->findPublishedByKind(PageKind::PartyPhotos);

        return null === $page
            ? $this->redirectToRoute('app_home')
            : $this->redirectToRoute('app_page', ['slug' => $page->getSlug()]);
    }
}
