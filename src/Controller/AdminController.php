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

class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private FileRepository $fileRepository,
    )
    {
    }

    #[Route('/filemanager', name: 'app_dashboard')]
    public function index(): Response
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

}
