<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeoController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], priority: 10)]
    public function sitemap(PageRepository $pages): Response
    {
        $response = $this->render('front/seo/sitemap.xml.twig', ['pages' => $pages->findPublished()]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'], priority: 10)]
    public function robots(): Response
    {
        $response = $this->render('front/seo/robots.txt.twig');
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
