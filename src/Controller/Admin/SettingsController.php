<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\SiteSettingsFormType;
use App\Settings\SettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    #[Route('/admin/parametres', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function edit(Request $request, SettingsProvider $settingsProvider): Response
    {
        $settings = $settingsProvider->get();
        $form = $this->createForm(SiteSettingsFormType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $settingsProvider->save($settings);
            $this->addFlash('success', 'Les paramètres ont été enregistrés.');

            return $this->redirectToRoute('admin_settings', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/settings/edit.html.twig', ['form' => $form]);
    }
}
