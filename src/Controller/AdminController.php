<?php

namespace App\Controller;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Consultation;
use App\Entity\Speciality;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Statistiques
        $medecinRepo = $em->getRepository(Medecin::class);
        $patientRepo = $em->getRepository(Patient::class);
        $consultationRepo = $em->getRepository(Consultation::class);

        $nbMedecins = $medecinRepo->count([]);
        $nbPatients = $patientRepo->count([]);
        $nbConsultations = $consultationRepo->count([]);

        // Consultations d'aujourd'hui (Approximation, idéalement une requête custom)
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $nbConsultationsJour = $consultationRepo->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.date >= :today')
            ->andWhere('c.date < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les médecins non vérifiés
        $medecinsNonVerifies = $medecinRepo->findBy(['estVerifie' => false]);

        // Récupérer toutes les spécialités pour le dropdown
        $specialites = $em->getRepository(Speciality::class)->findAll();

        // Répartition par spécialité (Optimisé pour Doughnut Chart)
        $repartitionSpecialites = $medecinRepo->createQueryBuilder('m')
            ->select('s.nom as specialite, COUNT(m.id) as total')
            ->leftJoin('m.specialite', 's')
            ->where('m.estVerifie = true')
            ->groupBy('s.id')
            ->getQuery()
            ->getResult();

        $consultationsParJour = [];
        $labelsJours = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $dateDebut = (clone $date)->setTime(0, 0, 0);
            $dateFin = (clone $date)->setTime(23, 59, 59);

            // Compter consultations de ce jour
            $nbConsult = $consultationRepo->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.date >= :debut')
                ->andWhere('c.date <= :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin)
                ->getQuery()
                ->getSingleScalarResult();

            $consultationsParJour[] = $nbConsult;

            // Format label: "Lun 06/01" 
            $labelsJours[] = $date->format('D d/m');
        }

        return $this->render('admin/dashboard.html.twig', [
            'nbMedecins' => $nbMedecins,
            'nbPatients' => $nbPatients,
            'nbConsultations' => $nbConsultations,
            'nbConsultationsJour' => $nbConsultationsJour,
            'repartitionSpecialites' => $repartitionSpecialites,
            'consultationsParJour' => $consultationsParJour,
            'labelsJours' => $labelsJours,
            'medecinsNonVerifies' => $medecinsNonVerifies,
        ]);
    }

    #[Route('/medecins', name: 'app_admin_medecins')]
    public function medecins(Request $request, EntityManagerInterface $em): Response
    {
        $searchTerm = $request->query->get('q');
        $medecinRepo = $em->getRepository(Medecin::class);

        if ($searchTerm) {
            $medecins = $medecinRepo->searchByTerm($searchTerm);
        } else {
            $medecins = $medecinRepo->findAll();
        }

        // Needed for the sidebar badge count
        $medecinsNonVerifiesCount = $medecinRepo->count(['estVerifie' => false]);

        return $this->render('admin/medecins.html.twig', [
            'medecins' => $medecins,
            'searchTerm' => $searchTerm,
            'medecinsNonVerifiesCount' => $medecinsNonVerifiesCount
        ]);
    }

    #[Route('/demandes', name: 'app_admin_demandes')]
    public function demandes(EntityManagerInterface $em): Response
    {
        $medecinRepo = $em->getRepository(Medecin::class);
        $medecinsNonVerifies = $medecinRepo->findBy(['estVerifie' => false]);
        $specialites = $em->getRepository(Speciality::class)->findAll();

        return $this->render('admin/demandes.html.twig', [
            'medecinsNonVerifies' => $medecinsNonVerifies,
            'specialites' => $specialites,
        ]);
    }

    #[Route('/medecin/{id}/valider', name: 'app_admin_valider_medecin', methods: ['POST'])]
    public function validerMedecin(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('valider_medecin_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $medecin = $em->getRepository(Medecin::class)->find($id);

        if (!$medecin) {
            throw $this->createNotFoundException('Médecin non trouvé');
        }

        $specialiteId = $request->request->get('specialite');
        if ($specialiteId) {
            $specialite = $em->getRepository(Speciality::class)->find($specialiteId);
            if ($specialite) {
                $medecin->setSpecialite($specialite);
            }
        }

        $medecin->setEstVerifie(true);
        $em->flush();

        $this->addFlash('success', 'Médecin validé avec succès.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/medecin/{id}/refuser', name: 'app_admin_refuser_medecin', methods: ['POST'])]
    public function refuserMedecin(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('refuser_medecin_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $medecin = $em->getRepository(Medecin::class)->find($id);

        if (!$medecin) {
            throw $this->createNotFoundException('Médecin non trouvé');
        }

        $em->remove($medecin);
        $em->flush();

        $this->addFlash('warning', 'Demande médecin refusée et supprimée.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/medecin/{id}/delete', name: 'app_admin_delete_medecin', methods: ['POST'])]
    public function deleteMedecin(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_medecin_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_medecins');
        }

        $medecin = $em->getRepository(Medecin::class)->find($id);

        if (!$medecin) {
            $this->addFlash('error', 'Médecin non trouvé.');
            return $this->redirectToRoute('app_admin_medecins');
        }

        try {
            $nomComplet = 'Dr. ' . $medecin->getPrenom() . ' ' . $medecin->getNom();
            $em->remove($medecin);
            $em->flush();
            $this->addFlash('success', $nomComplet . ' a été supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_admin_medecins');
    }

    #[Route('/profile/update', name: 'app_admin_update_profile', methods: ['POST'])]
    public function updateProfile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_update_profile', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $telephone = $request->request->get('telephone');
        $newPassword = $request->request->get('new_password');

        if ($nom) $user->setNom($nom);
        if ($prenom) $user->setPrenom($prenom);
        if ($telephone) $user->setTelephone($telephone);

        if (!empty($newPassword)) {
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $em->flush();
        $this->addFlash('success', 'Profil modifié avec succès !');

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/patients', name: 'app_admin_patients')]
    public function patients(Request $request, EntityManagerInterface $em): Response
    {
        $searchTerm = $request->query->get('q');
        $patientRepo = $em->getRepository(Patient::class);

        if ($searchTerm) {
            $patients = $patientRepo->searchByTerm($searchTerm);
        } else {
            $patients = $patientRepo->findAll();
        }

        $medecinRepo = $em->getRepository(Medecin::class);
        $medecinsNonVerifiesCount = $medecinRepo->count(['estVerifie' => false]);

        return $this->render('admin/patients.html.twig', [
            'patients' => $patients,
            'medecinsNonVerifiesCount' => $medecinsNonVerifiesCount,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/patient/{id}/delete', name: 'app_admin_delete_patient', methods: ['POST'])]
    public function deletePatient(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_patient_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_patients');
        }

        $patient = $em->getRepository(Patient::class)->find($id);

        if (!$patient) {
            $this->addFlash('error', 'Patient non trouvé.');
            return $this->redirectToRoute('app_admin_patients');
        }

        try {
            $nomComplet = $patient->getPrenom() . ' ' . $patient->getNom();
            $em->remove($patient);
            $em->flush();
            $this->addFlash('success', $nomComplet . ' a été supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_admin_patients');
    }
}
