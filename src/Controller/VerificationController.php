<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class VerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private UserRepository $userRepository
    ) {}

    #[Route('/verify/resend', name: 'app_resend_verification')]
    public function resendVerification(Request $request): Response
    {
        // Try to get user from session first
        $user = $this->getUser();
        
        // If not logged in, try to get user from email parameter
        if (!$user) {
            $email = $request->query->get('email');
            if (!$email) {
                throw new AccessDeniedException('Email parameter is required when not logged in.');
            }
            
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'User not found with this email.');
                return $this->redirectToRoute('app_login');
            }
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->emailVerificationService->sendVerificationEmail($user);
            $this->addFlash('success', 'Verification email has been sent. Please check your inbox.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Could not send verification email. Please try again later.');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, Request $request): Response
    {
        try {
            $this->emailVerificationService->verifyEmail($token);
            $this->addFlash('success', 'Your email has been verified! You can now log in.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_login');
    }
}
