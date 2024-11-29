<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\EmailVerification;
use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationService
{
    private string $fromEmail;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private EmailVerificationRepository $verificationRepository,
        string $fromEmail
    ) {
        $this->fromEmail = $fromEmail;
    }

    public function sendVerificationEmail(User $user): void
    {
        // Delete any existing verification tokens for this user
        $existingVerifications = $this->verificationRepository->findBy(['user' => $user]);
        foreach ($existingVerifications as $verification) {
            $this->entityManager->remove($verification);
        }
        $this->entityManager->flush();

        // Generate verification token
        $token = bin2hex(random_bytes(32));
        
        // Create verification entity
        $verification = new EmailVerification(
            $user,
            $token,
            (new \DateTime())->modify('+24 hours')
        );

        // Save to database
        $this->entityManager->persist($verification);
        $this->entityManager->flush();

        // Generate verification URL
        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Create email
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Please verify your email')
            ->html(
                $this->getEmailTemplate($user->getName(), $verificationUrl)
            );

        // Send email
        $this->mailer->send($email);
    }

    public function verifyEmail(string $token): void
    {
        $verification = $this->verificationRepository->findOneBy(['token' => $token]);

        if (!$verification) {
            throw new \Exception('Invalid verification token.');
        }

        if ($verification->getExpiresAt() < new \DateTime()) {
            throw new \Exception('Verification token has expired. Please request a new one.');
        }

        $user = $verification->getUser();
        $user->setIsVerified(true);

        // Remove the verification token
        $this->entityManager->remove($verification);
        $this->entityManager->flush();
    }

    private function getEmailTemplate(string $userName, string $verificationUrl): string
    {
        return "
            <h1>Welcome to FlexiStock!</h1>
            <p>Hi {$userName},</p>
            <p>Please verify your email address by clicking the button below:</p>
            <p>
                <a href='{$verificationUrl}' 
                   style='background-color: #4CAF50; 
                          color: white; 
                          padding: 10px 20px; 
                          text-decoration: none; 
                          border-radius: 5px;'>
                    Verify Email
                </a>
            </p>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't create an account, you can safely ignore this email.</p>
            <p>
                <small>If the button doesn't work, copy and paste this URL into your browser:</small><br>
                <small>{$verificationUrl}</small>
            </p>
        ";
    }
}
