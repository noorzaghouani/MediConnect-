<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\MedecinRepository;

class PatientController extends AbstractController
{
    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    #[IsGranted('ROLE_PATIENT')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $specialities = $em->getRepository(\App\Entity\Speciality::class)->findAll();
        return $this->render('patient/dashboard.html.twig', [
            'specialities' => $specialities
        ]);
    }

    #[Route('/patient/search-medecin', name: 'app_patient_search_medecin', methods: ['GET'])]
    #[IsGranted('ROLE_PATIENT')]
    public function searchMedecin(Request $request, MedecinRepository $medecinRepository): JsonResponse
    {
        $nom = $request->query->get('nom', '');
        $prenom = $request->query->get('prenom', '');
        $specialite = $request->query->get('specialite', '');

        $medecins = $medecinRepository->searchMedecins($nom, $prenom, $specialite);

        $results = [];
        foreach ($medecins as $medecin) {
            $results[] = [
                'id' => $medecin->getId(),
                'nom' => $medecin->getNom(),
                'prenom' => $medecin->getPrenom(),
                'specialite' => $medecin->getSpecialite()?->getNom() ?? 'Non spécifiée',
                'telephone' => $medecin->getTelephone() ?? '',
                'email' => $medecin->getEmail(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'count' => count($results),
            'data' => $results
        ]);
    }

    #[Route('/patient/edit-profile', name: 'app_patient_edit_profile', methods: ['POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        $data = $request->request->all();

        // Update personal information
        if (isset($data['nom']) && !empty($data['nom'])) {
            $patient->setNom($data['nom']);
        }
        if (isset($data['prenom']) && !empty($data['prenom'])) {
            $patient->setPrenom($data['prenom']);
        }
        if (isset($data['telephone']) && !empty($data['telephone'])) {
            $patient->setTelephone($data['telephone']);
        }

        // Update password if provided
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            // Verify current password
            if ($passwordHasher->isPasswordValid($patient, $data['current_password'])) {
                if ($data['new_password'] === $data['confirm_password']) {
                    $hashedPassword = $passwordHasher->hashPassword($patient, $data['new_password']);
                    $patient->setPassword($hashedPassword);
                } else {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_patient_dashboard');
                }
            } else {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
                return $this->redirectToRoute('app_patient_dashboard');
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('app_patient_dashboard');
    }
}