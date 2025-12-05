<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, on le redirige vers SON dashboard
        if ($this->getUser()) {
            return $this->redirectToDashboard();
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier email saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        // Afficher la page de connexion
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    private function redirectToDashboard()
    {
        $user = $this->getUser();
        
        // Redirection selon le rôle - AVEC LES BONS NOMS DE ROUTES
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        if (in_array('ROLE_MEDECIN', $user->getRoles())) {
            return $this->redirectToRoute('app_medecin_dashboard');
        }
        
        // Par défaut, dashboard patient
        return $this->redirectToRoute('app_patient_dashboard');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode sera interceptée par le firewall de déconnexion.');
    }
}