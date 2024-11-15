<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user', name: 'admin_user_')]
class AdminUserController extends AbstractController
{
    // Afficher la liste des utilisateurs
    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Vérifie si l'utilisateur est administrateur
        $users = $entityManager->getRepository(User::class)->findAll();  // Récupère tous les utilisateurs

        return $this->render('admin_user/index.html.twig', [
            'users' => $users,  // Passe les utilisateurs à la vue
        ]);
    }

    // Créer un nouvel utilisateur
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the plain password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            // Persist the new user entity
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a bien été créé.');

            // Redirect to the user listing page
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin_user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Modifier un utilisateur existant
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);  // Crée le formulaire pour l'utilisateur

        $form->handleRequest($request);  // Gère la soumission du formulaire

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un mot de passe est fourni, on le hache et on le sauvegarde
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Sauvegarde les modifications dans la base de données
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a bien été modifié.');

            return $this->redirectToRoute('admin_user_index');  // Redirige vers la liste des utilisateurs
        }

        return $this->render('admin_user/edit.html.twig', [
            'form' => $form->createView(),  // Passe le formulaire à la vue
            'user' => $user,  // Passe l'utilisateur à la vue pour l'affichage
        ]);
    }

    // Supprimer un utilisateur
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérifie le token CSRF pour la suppression
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

        return $this->redirectToRoute('admin_user_index');  // Redirige vers la liste des utilisateurs
    }
}