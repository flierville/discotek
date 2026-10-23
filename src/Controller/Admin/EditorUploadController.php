<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Pictures inserted in the pages with the WYSIWYG editor (TinyMCE "images_upload_url").
 */
final class EditorUploadController extends AbstractController
{
    #[Route('/admin/editeur/image', name: 'admin_editor_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        ValidatorInterface $validator,
        SluggerInterface $slugger,
        #[Autowire('%app.page_upload_dir%')] string $uploadDir,
        #[Autowire('%app.page_upload_url%')] string $uploadUrl,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('editor-upload', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => ['message' => 'Jeton de sécurité invalide, rechargez la page.']], 403);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => ['message' => 'Aucun fichier reçu.']], 400);
        }

        $violations = $validator->validate($file, new Image(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp']));
        if (\count($violations) > 0) {
            return $this->json(['error' => ['message' => (string) $violations->get(0)->getMessage()]], 422);
        }

        $name = \sprintf(
            '%s-%s.%s',
            $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->lower()->truncate(60),
            bin2hex(random_bytes(4)),
            $file->guessExtension() ?? 'jpg',
        );
        $file->move($uploadDir, $name);

        return $this->json(['location' => $uploadUrl.'/'.$name]);
    }
}
