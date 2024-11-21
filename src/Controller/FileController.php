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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileController extends AbstractController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
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

            $File = new File();
            $File->setTitle($title);
            $File->setDescription($description);
            $File->setPermission($permission);
            $File->setExpireAfter($expireAfter);
            $File->setCreatedAt(new \DateTimeImmutable());
            $File->setFileId($newFilename);
            $File->setFileName($fileName);

            $entityManager->persist($File);
            $entityManager->flush();

            return new Response('Datei erfolgreich hochgeladen und gespeichert!', Response::HTTP_OK);
        }

        return new Response('Fehler beim Hochladen der Datei.', Response::HTTP_BAD_REQUEST);
    }
}