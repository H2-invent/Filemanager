<?php

// src/Controller/FileController.php
namespace App\Controller;

use App\Entity\File;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileController extends AbstractController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[Route('/filemanager/upload', name: 'file_upload', methods: ['POST'])]
    public function uploadFile(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $authHeader = $request->headers->get('Authorization');

        $token = null;
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7); // Entfernt "Bearer " vom Anfang
        }

        $this->authService->validateToken($token);

        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $permission = $request->request->get('permission');
        $expireAfter = $request->request->get('expire');
        $fileContent = $request->files->get('file');

        if ($fileContent instanceof UploadedFile) {
            $originalFilename = pathinfo($fileContent->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '-' . $fileContent->guessExtension();
            $fileName = $originalFilename . '.' . $fileContent->guessExtension();
            $fileContent->move($this->getParameter('upload_directory'), $newFilename);

            $file = new File();
            $file->setTitle($title);
            $file->setDescription($description);
            $file->setPermission($permission);
            $file->setExpireAfter($expireAfter !== null ? (int) $expireAfter : null);
            $file->setCreatedAt(new \DateTimeImmutable());
            $file->setFileId($newFilename);
            $file->setFileName($fileName);

            $entityManager->persist($file);
            $entityManager->flush();

            return new Response('Datei erfolgreich hochgeladen und gespeichert!', Response::HTTP_OK);
        }

        return new Response('Fehler beim Hochladen der Datei.', Response::HTTP_BAD_REQUEST);
    }
}
