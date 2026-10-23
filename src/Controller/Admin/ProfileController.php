<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    #[Route('/admin/mon-compte', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('newPassword')->getData()));
            $entityManager->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('admin_profile', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/profile/edit.html.twig', ['form' => $form]);
    }
}
