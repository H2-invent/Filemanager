<?php

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem,
        private readonly FileRepository $fileRepository,
    ) {
    }

    #[Route('/filemanager', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $data = $this->fileRepository->findFilesByEmail($user->getEmail());

        return $this->render('dashboard/index.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/filemanager/download/{id}', name: 'app_dashboard_download')]
    public function downloadAction(string $id): Response
    {
        $file = $this->fileRepository->findOneBy([
            'fileId'     => $id,
            'permission' => $this->getUser()->getEmail(),
        ]);

        if (!$file) {
            throw $this->createNotFoundException('Die Datei wurde nicht gefunden.');
        }

        $filePath = $this->getParameter('upload_directory') . '/' . $file->getFileId();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Die Datei wurde nicht gefunden.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFileName());

        return $response;
    }

    #[Route('/filemanager/delete/{id}', name: 'app_dashboard_delete')]
    public function deleteAction(string $id): Response
    {
        $file = $this->fileRepository->findOneBy([
            'fileId'     => $id,
            'permission' => $this->getUser()->getEmail(),
        ]);

        if (!$file) {
            throw $this->createNotFoundException('Die Datei wurde nicht gefunden.');
        }

        $filePath = $this->getParameter('upload_directory') . '/' . $file->getFileId();

        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }

        $this->entityManager->remove($file);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_dashboard');
    }
}
