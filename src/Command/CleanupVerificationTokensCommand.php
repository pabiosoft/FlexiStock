<?php

namespace App\Command;

use App\Repository\EmailVerificationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-verification-tokens',
    description: 'Removes expired email verification tokens'
)]
class CleanupVerificationTokensCommand extends Command
{
    private EmailVerificationRepository $verificationRepo;

    public function __construct(EmailVerificationRepository $verificationRepo)
    {
        parent::__construct();
        $this->verificationRepo = $verificationRepo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->verificationRepo->removeExpired();

        $io->success('Expired verification tokens have been removed.');

        return Command::SUCCESS;
    }
}
