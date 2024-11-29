<?php

namespace App\Controller;

use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        EmailVerificationRepository $verificationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        // Find verification request
        $verification = $verificationRepo->findByToken($token);

        if (!$verification) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_login');
        }

        if ($verification->isExpired()) {
            $entityManager->remove($verification);
            $entityManager->flush();
            
            $this->addFlash('error', 'This verification link has expired. Please request a new one.');
            return $this->redirectToRoute('app_login');
        }

        // Mark user as verified
        $user = $verification->getUser();
        $user->setIsVerified(true);

        // Remove verification request
        $entityManager->remove($verification);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EmailVerificationService $verificationService
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_home');
        }

        $verificationService->sendVerificationEmail($user);

        $this->addFlash('success', 'A new verification email has been sent.');
        return $this->redirectToRoute('app_home');
    }
}
