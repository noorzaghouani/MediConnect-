<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Medecin;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use App\Security\LoginAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, LoginAuthenticator $authenticator): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Créer l'utilisateur selon le type
            if ($data['role'] === 'medecin') {
                $user = new Medecin();
                // Gérer l'upload du diplôme ici
                if (isset($data['diplome']) && $data['diplome']) {
                    $diplomeFile = $data['diplome'];
                    $fileName = uniqid() . '.' . $diplomeFile->guessExtension();
                    $diplomeFile->move(
                        $this->getParameter('diplomes_directory'),
                        $fileName
                    );
                    $user->setDiplome($fileName);
                }
                // Gérer la spécialité
                if (isset($data['specialite']) && $data['specialite']) {
                    $user->setSpecialite($data['specialite']);
                }
            } else {
                $user = new Patient();
            }

            // Remplir les données communes
            $user->setEmail($data['email']);
            $user->setNom($data['nom']);
            $user->setPrenom($data['prenom']);
            $user->setTelephone($data['telephone']);
            $user->setGenre($data['genre']);
            $user->setDateNaissance($data['date_naissance']);

            // Encoder le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $data['password']
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Authentifier l'utilisateur automatiquement
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}