<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\MedecinRepository;
use App\Entity\Ordonnance;

class PatientController extends AbstractController
{
    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    public function dashboard(EntityManagerInterface $em, Request $request, MedecinRepository $medecinRepository): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        $specialities = $em->getRepository(\App\Entity\Speciality::class)->findAll();

        // Fetch real appointments
        $appointments = $em->getRepository(\App\Entity\RendezVous::class)->findBy(
            ['patient' => $patient],
            ['dateHeure' => 'ASC']
        );

        // Récupérer le dernier médecin consulté via Cookie
        $lastDoctor = null;
        $lastDoctorId = $request->cookies->get('last_doctor_id');

        if ($lastDoctorId) {
            $candidateDoctor = $medecinRepository->find($lastDoctorId);

            if ($candidateDoctor) {
                $now = new \DateTime();

                // Vérifier s'il existe un RDV annulé avec ce médecin
                $hasCancelledRdv = false;
                $hasFutureRdv = false;

                foreach ($appointments as $rdv) {
                    if ($rdv->getMedecin()->getId() === $candidateDoctor->getId()) {
                        // Vérifier s'il y a un RDV annulé
                        if ($rdv->getStatut() === 'annule') {
                            $hasCancelledRdv = true;
                        }

                        // Vérifier s'il y a un RDV futur actif
                        if (
                            $rdv->getDateHeure() > $now &&
                            in_array($rdv->getStatut(), ['en_attente', 'confirme'])
                        ) {
                            $hasFutureRdv = true;
                        }
                    }
                }

                // Afficher la suggestion SEULEMENT si :
                // 1. Il y a eu un RDV annulé avec ce médecin
                // 2. ET il n'y a pas de RDV futur actif
                if ($hasCancelledRdv && !$hasFutureRdv) {
                    $lastDoctor = $candidateDoctor;
                }
            }
        }

        return $this->render('patient/dashboard.html.twig', [
            'specialities' => $specialities,
            'appointments' => $appointments,
            'lastDoctor' => $lastDoctor
        ]);
    }

    #[Route('/patient/mon-dossier-medical', name: 'app_patient_mon_dossier_medical')]
    public function monDossierMedical(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        // Récupérer ou créer le dossier médical
        $dossier = $patient->getDossierMedical();

        if (!$dossier) {
            $dossier = new \App\Entity\DossierMedical();
            $dossier->setPatient($patient);
            $em->persist($dossier);
            $em->flush();
        }

        return $this->render('patient/mon_dossier.html.twig', [
            'patient' => $patient,
            'dossier' => $dossier,
            'returnRoute' => 'app_patient_dashboard'
        ]);
    }

    #[Route('/patient/ordonnances', name: 'app_patient_ordonnances')]
    public function ordonnances(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        // Récupérer toutes les ordonnances du patient
        $ordonnances = $em->getRepository(Ordonnance::class)->findBy(
            ['patient' => $patient],
            ['createdAt' => 'DESC']
        );

        return $this->render('patient/ordonnances.html.twig', [
            'ordonnances' => $ordonnances
        ]);
    }

    #[Route('/patient/search-medecin', name: 'app_patient_search_medecin', methods: ['GET'])]
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

    #[Route('/patient/rdv', name: 'app_patient_rdv')]
    public function prendreRdv(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        // Récupérer toutes les spécialités pour le filtre
        $specialites = $em->getRepository(\App\Entity\Speciality::class)->findAll();

        // Récupérer les paramètres de recherche
        $dateRecherche = $request->query->get('date');
        $specialiteId = $request->query->get('specialite');

        $disponibilites = [];

        // Si une date est fournie, rechercher les disponibilités
        if ($dateRecherche) {
            $qb = $em->createQueryBuilder();

            // Convertir la date en DateTime pour la requête
            $date = new \DateTime($dateRecherche);
            $dateDebut = (clone $date)->setTime(0, 0, 0);
            $dateFin = (clone $date)->setTime(23, 59, 59);

            // Requête DQL pour récupérer les disponibilités du jour
            $qb->select('d', 'm', 's')
                ->from(\App\Entity\Disponibilite::class, 'd')
                ->leftJoin('d.medecin', 'm')
                ->leftJoin('m.specialite', 's')
                ->where('d.dateDebut >= :dateDebut')
                ->andWhere('d.dateDebut <= :dateFin')
                ->andWhere('d.estDisponible = true') // Seulement les créneaux disponibles
                ->andWhere('m.estVerifie = true') // Seulement les médecins vérifiés
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin);

            // Filtre par spécialité si fourni
            if ($specialiteId) {
                $qb->andWhere('s.id = :specialiteId')
                    ->setParameter('specialiteId', $specialiteId);
            }

            // Ordonner par médecin puis par heure
            $qb->orderBy('m.nom', 'ASC')
                ->addOrderBy('d.dateDebut', 'ASC');

            $disponibilites = $qb->getQuery()->getResult();
        }

        return $this->render('patient/rdv.html.twig', [
            'specialites' => $specialites,
            'disponibilites' => $disponibilites,
            'dateRecherche' => $dateRecherche,
            'specialiteId' => $specialiteId
        ]);
    }

    #[Route('/patient/edit-profile', name: 'app_patient_edit_profile', methods: ['POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        if (!$this->isCsrfTokenValid('patient_edit_profile', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

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
    #[Route('/patient/rdv/reserver/{id}', name: 'app_patient_rdv_reserver', methods: ['POST'])]
    public function reserverRdv(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\Patient $patient */
        $patient = $this->getUser();

        if (!$this->isCsrfTokenValid('reserver_rdv_' . $id, $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token de sécurité invalide.'], 403);
        }

        // Récupérer la disponibilité
        $disponibilite = $em->getRepository(\App\Entity\Disponibilite::class)->find($id);

        if (!$disponibilite) {
            return new JsonResponse(['success' => false, 'message' => 'Disponibilité introuvable.'], 404);
        }

        if (!$disponibilite->isEstDisponible()) {
            return new JsonResponse(['success' => false, 'message' => 'Ce créneau n\'est plus disponible.'], 400);
        }

        // Créer le rendez-vous
        $rdv = new \App\Entity\RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($disponibilite->getMedecin());
        $rdv->setDateHeure($disponibilite->getDateDebut());
        $rdv->setStatut('en_attente');
        $rdv->setMotif('Consultation standard'); // Motif par défaut ou à demander via un modal
        $rdv->setCreatedAt(new \DateTimeImmutable());

        // Marquer la disponibilité comme prise
        $disponibilite->setEstDisponible(false);

        $em->persist($rdv);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Rendez-vous réservé avec succès !']);
    }
}