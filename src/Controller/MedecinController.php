<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Disponibilite;
use App\Entity\Speciality;

class MedecinController extends AbstractController
{
    #[Route('/medecin/dashboard', name: 'app_medecin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté (qui est un Medecin)
        $medecin = $this->getUser();
        $specialities = $em->getRepository(Speciality::class)->findAll();

        // Récupérer les rendez-vous du médecin
        $appointments = $em->getRepository(\App\Entity\RendezVous::class)->findBy(
            ['medecin' => $medecin],
            ['dateHeure' => 'ASC']
        );

        return $this->render('medecin/dashboard.html.twig', [
            'medecin' => $medecin,
            'specialities' => $specialities,
            'appointments' => $appointments
        ]);
    }

    #[Route('/medecin/patients', name: 'app_medecin_patients')]
    public function patients(EntityManagerInterface $em): Response
    {
        $medecin = $this->getUser();
        $patients = $em->getRepository(\App\Entity\Patient::class)->findPatientsByMedecin($medecin);

        return $this->render('medecin/patients.html.twig', [
            'patients' => $patients
        ]);
    }



    #[Route('/medecin/profil', name: 'app_medecin_profil')]
    public function profil(EntityManagerInterface $em): Response
    {
        $medecin = $this->getUser();
        $specialities = $em->getRepository(Speciality::class)->findAll();

        return $this->render('medecin/profil.html.twig', [
            'medecin' => $medecin,
            'specialities' => $specialities,
        ]);
    }

    #[Route('/medecin/edit-profile', name: 'app_medecin_edit_profile', methods: ['POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        $data = $request->request->all();

        // Update personal information
        if (isset($data['nom']) && !empty($data['nom'])) {
            $medecin->setNom($data['nom']);
        }
        if (isset($data['prenom']) && !empty($data['prenom'])) {
            $medecin->setPrenom($data['prenom']);
        }
        if (isset($data['specialite']) && !empty($data['specialite'])) {
            $speciality = $entityManager->getRepository(Speciality::class)->find($data['specialite']);
            if ($speciality) {
                $medecin->setSpecialite($speciality);
            }
        }
        if (isset($data['email']) && !empty($data['email'])) {
            $medecin->setEmail($data['email']);
        }
        if (isset($data['telephone']) && !empty($data['telephone'])) {
            $medecin->setTelephone($data['telephone']);
        }

        // Update password if provided
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            // Verify current password
            if ($passwordHasher->isPasswordValid($medecin, $data['current_password'])) {
                if ($data['new_password'] === $data['confirm_password']) {
                    $hashedPassword = $passwordHasher->hashPassword($medecin, $data['new_password']);
                    $medecin->setPassword($hashedPassword);
                } else {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_medecin_dashboard');
                }
            } else {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
                return $this->redirectToRoute('app_medecin_dashboard');
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('app_medecin_dashboard');
    }

    #[Route('/medecin/availability/add', name: 'app_medecin_add_availability', methods: ['POST'])]
    public function addAvailability(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        $data = json_decode($request->getContent(), true);

        try {
            $disponibilite = new Disponibilite();
            $disponibilite->setMedecin($medecin);

            $date = new \DateTime($data['date']);
            $heureDebut = \DateTime::createFromFormat('H:i', $data['heure_debut']);
            $heureFin = \DateTime::createFromFormat('H:i', $data['heure_fin']);

            $dateDebut = clone $date;
            $dateDebut->setTime((int) $heureDebut->format('H'), (int) $heureDebut->format('i'));

            $dateFin = clone $date;
            $dateFin->setTime((int) $heureFin->format('H'), (int) $heureFin->format('i'));

            $disponibilite->setDateDebut($dateDebut);
            $disponibilite->setDateFin($dateFin);

            $entityManager->persist($disponibilite);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Disponibilité ajoutée avec succès',
                'data' => [
                    'id' => $disponibilite->getId(),
                    'date' => $date->format('Y-m-d'),
                    'heure_debut' => $heureDebut->format('H:i'),
                    'heure_fin' => $heureFin->format('H:i')
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/medecin/availability/list', name: 'app_medecin_list_availabilities', methods: ['GET'])]
    public function listAvailabilities(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        $disponibilites = $entityManager->getRepository(Disponibilite::class)
            ->findBy(['medecin' => $medecin], ['dateDebut' => 'ASC']);

        $data = [];
        foreach ($disponibilites as $dispo) {
            $data[] = [
                'id' => $dispo->getId(),
                'date' => $dispo->getDateDebut()->format('Y-m-d'),
                'heure_debut' => $dispo->getDateDebut()->format('H:i'),
                'heure_fin' => $dispo->getDateFin()->format('H:i'),
                'disponible' => $dispo->isEstDisponible()
            ];
        }

        return new JsonResponse(['data' => $data]);
    }

    #[Route('/medecin/availability/delete/{id}', name: 'app_medecin_delete_availability', methods: ['DELETE'])]
    public function deleteAvailability(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        $disponibilite = $entityManager->getRepository(Disponibilite::class)->find($id);

        if (!$disponibilite || $disponibilite->getMedecin() !== $medecin) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Disponibilité non trouvée'
            ], 404);
        }

        $entityManager->remove($disponibilite);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Disponibilité supprimée'
        ]);
    }
}