<?php

namespace App\Command;

use App\Service\FileExpirationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:file:expiration',
    description: 'Remove expired files',
)]
class FileExpirationCommand extends Command
{
    public function __construct(private readonly FileExpirationService $fileExpirationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deletedFiles = $this->fileExpirationService->deleteExpiredFiles();

        $io->success(count($deletedFiles) . ' expired Files deleted.');

        return Command::SUCCESS;
    }
}
