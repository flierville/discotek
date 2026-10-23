<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\PageKind;
use App\Repository\PageRepository;
use App\Repository\PartyRepository;
use App\Party\GalleryAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Permanently redirects the URLs of the old PHP site (indexed by search engines and sent by
 * email) to the new ones:
 *   /?nom=contact&page=pages&pageid=3            -> /contact
 *   /?page=photos&photoid=12&code=lila20         -> gallery of the party (access granted)
 *   /?page=message, /index.php                   -> home.
 */
final readonly class LegacyUrlRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageRepository $pages,
        private PartyRepository $parties,
        private GalleryAccess $galleryAccess,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Before the router (priority 32) so that /index.php does not end in a 404.
        return [KernelEvents::REQUEST => ['onKernelRequest', 40]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !\in_array($request->getPathInfo(), ['/', '/index.php'], true)) {
            return;
        }

        $query = $request->query;
        $target = match (true) {
            $query->has('pageid') => $this->pageUrl($query->getInt('pageid')),
            'photos' === $query->get('page') && $query->has('code') => $this->galleryUrl((string) $query->get('code')),
            $query->has('page'), $query->has('nom'), '/index.php' === $request->getPathInfo() => $this->urlGenerator->generate('app_home'),
            default => null,
        };

        if (null !== $target) {
            $event->setResponse(new RedirectResponse($target, Response::HTTP_MOVED_PERMANENTLY));
        }
    }

    private function pageUrl(int $legacyId): string
    {
        $page = $this->pages->findOneBy(['legacyId' => $legacyId, 'published' => true]);

        return null === $page || $page->isHome()
            ? $this->urlGenerator->generate('app_home')
            : $this->urlGenerator->generate('app_page', ['slug' => $page->getSlug()]);
    }

    private function galleryUrl(string $code): string
    {
        $party = $this->parties->findPublishedByCode($code);
        if (null === $party) {
            $photosPage = $this->pages->findPublishedByKind(PageKind::PartyPhotos);

            return null === $photosPage
                ? $this->urlGenerator->generate('app_home')
                : $this->urlGenerator->generate('app_page', ['slug' => $photosPage->getSlug()]);
        }

        // The old emails contained the code in the link: it still opens the gallery.
        $this->galleryAccess->grant($party);

        return $this->urlGenerator->generate('app_party_gallery', ['slug' => $party->getSlug()]);
    }
}
