<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Disponibilite;
use App\Entity\Medecin;
use App\Entity\Patient;
use App\Form\DisponibiliteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MedecinController extends AbstractController
{
    #[Route('/medecin/dashboard', name: 'app_medecin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        // Fetch upcoming appointments with patient loaded (optimized)
        $appointments = $entityManager->getRepository(RendezVous::class)
            ->findByMedecinWithPatient($medecin, 10);

        // Fetch specialities for the edit profile modal
        $specialities = $entityManager->getRepository(\App\Entity\Speciality::class)->findAll();

        return $this->render('medecin/dashboard.html.twig', [
            'appointments' => $appointments,
            'specialities' => $specialities
        ]);
    }

    #[Route('/medecin/patients', name: 'app_medecin_patients')]
    public function patients(EntityManagerInterface $entityManager): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        // ✅ OPTIMISÉ: Utilise la méthode repository qui évite les requêtes N+1
        $patientsWithRdv = $entityManager->getRepository(Patient::class)
            ->findPatientsWithNextRdvByMedecin($medecin);

        return $this->render('medecin/patients.html.twig', [
            'patientsWithRdv' => $patientsWithRdv
        ]);
    }

    #[Route('/medecin/edit-profile', name: 'app_medecin_edit_profile', methods: ['POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $telephone = $request->request->get('telephone');
        $email = $request->request->get('email');
        $specialiteId = $request->request->get('specialite');

        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!empty($nom)) {
            $medecin->setNom($nom);
        }

        if (!empty($prenom)) {
            $medecin->setPrenom($prenom);
        }

        if (!empty($telephone)) {
            $medecin->setTelephone($telephone);
        }

        if (!empty($email)) {
            $medecin->setEmail($email);
        }

        if (!empty($specialiteId)) {
            $specialite = $entityManager->getRepository(\App\Entity\Speciality::class)->find($specialiteId);
            if ($specialite) {
                $medecin->setSpecialite($specialite);
            }
        }

        // Handle password change
        if (!empty($currentPassword) && !empty($newPassword)) {
            if ($passwordHasher->isPasswordValid($medecin, $currentPassword)) {
                if ($newPassword === $confirmPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($medecin, $newPassword);
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

    #[Route('/medecin/disponibilites', name: 'app_medecin_disponibilites')]
    public function disponibilites(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        // Créer le formulaire
        $disponibilite = new Disponibilite();
        $disponibilite->setMedecin($medecin);
        $form = $this->createForm(DisponibiliteType::class, $disponibilite);

        // Gérer la soumission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Récupérer les données du formulaire
                $date = $form->get('date')->getData();
                $heureDebut = $form->get('heureDebut')->getData();
                $heureFin = $form->get('heureFin')->getData();

                // VALIDATION: Date ne peut pas être dans le passé (sécurité serveur)
                $today = new \DateTime('today');
                if ($date < $today) {
                    $this->addFlash('error', 'Vous ne pouvez pas créer de disponibilités dans le passé');
                    return $this->redirectToRoute('app_medecin_disponibilites');
                }

                // Créer DateTime de début et fin de plage
                $dateDebut = new \DateTime($date->format('Y-m-d') . ' ' . $heureDebut->format('H:i'));
                $dateFin = new \DateTime($date->format('Y-m-d') . ' ' . $heureFin->format('H:i'));

                // Vérifier que fin > début
                if ($dateFin <= $dateDebut) {
                    $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début');
                    return $this->redirectToRoute('app_medecin_disponibilites');
                }

                $createdCount = 0;

                // Créer les créneaux de 20 minutes
                $currentStart = clone $dateDebut;
                while ($currentStart < $dateFin) {
                    $currentEnd = clone $currentStart;
                    $currentEnd->modify('+20 minutes');

                    // Ne pas dépasser la fin de plage
                    if ($currentEnd > $dateFin) {
                        break;
                    }

                    // Créer le créneau
                    $dispo = new Disponibilite();
                    $dispo->setMedecin($medecin);
                    $dispo->setDateDebut(clone $currentStart);
                    $dispo->setDateFin(clone $currentEnd);
                    $dispo->setEstDisponible(true);

                    $em->persist($dispo);
                    $createdCount++;

                    // Passer au créneau suivant
                    $currentStart = clone $currentEnd;
                }

                // Sauvegarder tout
                $em->flush();

                $this->addFlash('success', "{$createdCount} créneau(x) créé(s) avec succès");
                return $this->redirectToRoute('app_medecin_disponibilites');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        // Récupérer les disponibilités
        $disponibilites = $em->getRepository(Disponibilite::class)
            ->findByMedecinSorted($medecin);

        return $this->render('medecin/disponibilites.html.twig', [
            'disponibilites' => $disponibilites,
            'form' => $form->createView()
        ]);
    }

    #[Route('/medecin/disponibilites/{id}/delete', name: 'app_medecin_disponibilite_delete', methods: ['POST'])]
    public function deleteDisponibilite(int $id, EntityManagerInterface $em): Response
    {
        /** @var Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->redirectToRoute('app_medecin_dashboard');
        }

        $disponibilite = $em->getRepository(Disponibilite::class)->find($id);

        if (!$disponibilite) {
            $this->addFlash('error', 'Disponibilité introuvable.');
            return $this->redirectToRoute('app_medecin_disponibilites');
        }

        // Vérifier que la disponibilité appartient bien au médecin
        if ($disponibilite->getMedecin() !== $medecin) {
            throw $this->createAccessDeniedException();
        }

        try {
            $repository = $em->getRepository(Disponibilite::class);
            $repository->remove($disponibilite, true);

            $this->addFlash('success', 'Disponibilité supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_medecin_disponibilites');
    }
}