<?php

namespace App\Controller;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Form\RegistrationFormType;
use App\Repository\SpecialityRepository;
use App\Security\LoginAuthenticator;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginAuthenticator $authenticator, EntityManagerInterface $entityManager, SpecialityRepository $specialityRepository, FileUploadService $fileUploadService): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['password'] !== $data['confirm_password']) {
              $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
              return $this->redirectToRoute('app_register');
            }
            // Vérifier si l'email existe déjà
            $existingUser = $entityManager->getRepository(Patient::class)->findOneBy(['email' => $data['email']]);
            if (!$existingUser) {
                $existingUser = $entityManager->getRepository(Medecin::class)->findOneBy(['email' => $data['email']]);
            }

            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                return $this->redirectToRoute('app_register');
            }

            if ($data['role'] === 'medecin') {
                $diplomeFile = $form->get('diplome')->getData();
                $specialite  = $form->get('specialite')->getData();

                // L1 fix : vérification côté serveur — diplôme et spécialité obligatoires
                if (!$diplomeFile || !$specialite) {
                    $this->addFlash('error', 'Le diplôme et la spécialité sont obligatoires pour un compte médecin.');
                    return $this->redirectToRoute('app_register');
                }

                $user = new Medecin();

                // Upload sécurisé du diplôme
                try {
                    $fileName = $fileUploadService->upload(
                        $diplomeFile,
                        $this->getParameter('diplomes_directory')
                    );
                    $user->setDiplome($fileName);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_register');
                }

                $user->setSpecialite($specialite);
            } else {
                $user = new Patient();
            }

            $user->setNom($data['nom']);
            $user->setPrenom($data['prenom']);
            $user->setTelephone($data['telephone']);
            $user->setDateNaissance($data['date_naissance']);
            $user->setGenre($data['genre']);
            $user->setEmail($data['email']);

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $data['password']
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        $specialities = $specialityRepository->findAll();

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'specialities' => $specialities,
        ]);
    }
}