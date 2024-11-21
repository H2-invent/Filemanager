<?php

// src/Service/FileExpirationService.php
namespace App\Service;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileExpirationService
{
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private string $uploadDirectory;
    private string $defaultExpireAfter;

    public function __construct(EntityManagerInterface $entityManager, string $uploadDirectory, string $defaultExpireAfter)
    {
        $this->entityManager = $entityManager;
        $this->filesystem = new Filesystem();
        $this->uploadDirectory = $uploadDirectory;
        $this->defaultExpireAfter = $defaultExpireAfter;
    }

    /**
     * Löscht Dateien, deren Ablaufdatum überschritten ist.
     *
     * @return array Liste der gelöschten Dateien.
     */
    public function deleteExpiredFiles(): array
    {
        $deletedFiles = [];
        $now = new \DateTimeImmutable();

        // Finde Dateien, deren Ablaufdatum überschritten ist
        $files = $this->entityManager->getRepository(File::class)->findAll();

        foreach ($files as $file) {
            /** @var File $file */
            $createdAt = $file->getCreatedAt();
            $expireAfter = $file->getExpireAfter();

            if ($createdAt instanceof \DateTimeImmutable) {
                
                if ($expireAfter === null) {
                    $expirationDate = $createdAt->modify("+{$this->defaultExpireAfter} hours");
                } else {
                    $expirationDate = $createdAt->modify("+{$expireAfter} hours");
                }

                // Datei ist abgelaufen
                if ($expirationDate < $now) {
                    $filePath = $this->uploadDirectory . '/' . $file->getFileId();

                    // Lösche die Datei vom Dateisystem
                    if ($this->filesystem->exists($filePath)) {
                        $this->filesystem->remove($filePath);
                        $deletedFiles[] = $filePath;
                    }

                    // Lösche den Eintrag aus der Datenbank
                    $this->entityManager->remove($file);
                }
            }
        }

        // Änderungen in der Datenbank speichern
        $this->entityManager->flush();

        return $deletedFiles;
    }
}
