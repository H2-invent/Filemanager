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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private Filesystem $filesystem,
        private FileRepository $fileRepository,
    )
    {
        $this->filesystem = new Filesystem();
    }

    #[Route('/', name: 'app_start')]
    public function start(): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/filemanager', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $data = $this->fileRepository->findFilesByEmail($user->getEmail());
        return $this->render('dashboard/index.html.twig', [
            'data' => $data
        ]);
    }

    #[Route('/filemanager/download/{id}', name: 'app_dashboard_download')]
    public function downloadAction($id)
    {

    $file = $this->fileRepository->findOneBy(
            array(
                'fileId' => $id,
                'permission' => $this->getUser()->getEmail()
            )
    );
        // Definiere den Pfad zum data-Verzeichnis
        $filePath = $this->getParameter('upload_directory') . '/' . $file->getFileId();

        // Überprüfen, ob die Datei existiert
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Die Datei wurde nicht gefunden.');
        }

        // Erstelle die BinaryFileResponse
        $response = new BinaryFileResponse($filePath);

        // Setze den Header, um den Download zu initiieren
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFileName());

        return $response;
    }

    #[Route('/filemanager/delete/{id}', name: 'app_dashboard_delete')]
    public function deleteAction($id)
    {

    $file = $this->fileRepository->findOneBy(
            array(
                'fileId' => $id,
                'permission' => $this->getUser()->getEmail()
            )
    );

    $filePath = $this->getParameter('upload_directory') . '/' . $file->getFileId();

        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }

        $this->entityManager->remove($file);
        $this->entityManager->flush();
        return $this->redirectToRoute('app_dashboard');
    }

}
