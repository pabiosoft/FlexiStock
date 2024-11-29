<?php

namespace App\Command;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-email-verification',
    description: 'Test email verification system by creating a test user'
)]
class TestEmailVerificationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationService $verificationService,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create a test user with unique email
        $timestamp = time();
        $testEmail = "test.user.{$timestamp}@example.com";
        
        $user = new User();
        $user->setEmail($testEmail);
        $user->setName("Test User {$timestamp}");
        
        // Set a test password
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'test123');
        $user->setPassword($hashedPassword);

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Test user created with email: {$testEmail}");

        // Send verification email
        try {
            $this->verificationService->sendVerificationEmail($user);
            $io->success('Verification email sent successfully');
        } catch (\Exception $e) {
            $io->error('Failed to send verification email: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->note('User credentials:');
        $io->listing([
            "Email: {$testEmail}",
            'Password: test123',
            'Verified: ' . ($user->isVerified() ? 'Yes' : 'No')
        ]);

        return Command::SUCCESS;
    }
}
