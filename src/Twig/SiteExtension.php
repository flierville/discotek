<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Page;
use App\Entity\Settings;
use App\Repository\PageRepository;
use App\Settings\SettingsProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Layout data of the public site (menu, footer, settings) and page URLs.
 */
final class SiteExtension
{
    /** @var list<Page>|null */
    private ?array $menuPages = null;

    public function __construct(
        private readonly SettingsProvider $settingsProvider,
        private readonly PageRepository $pages,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsTwigFunction('site_settings')]
    public function settings(): Settings
    {
        return $this->settingsProvider->get();
    }

    /** @return list<Page> */
    #[AsTwigFunction('menu_pages')]
    public function menuPages(): array
    {
        return $this->menuPages ??= $this->pages->findMenuPages();
    }

    /** @return list<Page> */
    #[AsTwigFunction('footer_pages')]
    public function footerPages(): array
    {
        return $this->pages->findFooterPages();
    }

    #[AsTwigFunction('page_path')]
    public function pagePath(Page $page, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $page->isHome()
            ? $this->urlGenerator->generate('app_home', [], $referenceType)
            : $this->urlGenerator->generate('app_page', ['slug' => $page->getSlug()], $referenceType);
    }

    #[AsTwigFilter('euros')]
    public function euros(int|float $amount): string
    {
        return number_format($amount, 0, ',', ' ').' €';
    }
}
