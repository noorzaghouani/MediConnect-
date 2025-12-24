<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;




class RendezVousController extends AbstractController
{
    #[Route('/patient/book/{id}', name: 'app_patient_book_appointment', methods: ['POST'])]
    public function book(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();

        // Récupérer la disponibilité manuellement
        $disponibilite = $entityManager->getRepository(Disponibilite::class)->find($id);

        if (!$disponibilite) {
            $this->addFlash('error', 'Créneau non trouvé.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

        // 1. Vérifier si le créneau est toujours disponible
        if (!$disponibilite->isEstDisponible()) {
            $this->addFlash('error', 'Ce créneau n\'est plus disponible.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

        // 2. Créer le Rendez-vous
        $rdv = new RendezVous();
        $rdv->setPatient($patient);
        $rdv->setMedecin($disponibilite->getMedecin());
        $rdv->setDateHeure($disponibilite->getDateDebut());
        $rdv->setDate($disponibilite->getDateDebut());
        $rdv->setStatut(RendezVous::STATUT_ATTENTE); // Initialement "En attente"

        // Calculer la durée
        $diff = $disponibilite->getDateFin()->getTimestamp() - $disponibilite->getDateDebut()->getTimestamp();
        $rdv->setDuree($diff / 60);

        // 3. Marquer le créneau comme indisponible
        $disponibilite->setEstDisponible(false);

        // 4. Sauvegarder
        $entityManager->persist($rdv);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée. En attente de confirmation.');

        // Création du Cookie "Dernier Médecin"
        $response = $this->redirectToRoute('app_patient_dashboard');

        // Le cookie expire dans 30 jours (3600 * 24 * 30)
        $cookie = \Symfony\Component\HttpFoundation\Cookie::create(
            'last_doctor_id',
            $disponibilite->getMedecin()->getId(),
            time() + (3600 * 24 * 30)
        );

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/patient/cancel/{id}', name: 'app_patient_cancel_appointment', methods: ['POST'])]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le RDV appartient bien au patient connecté
        if ($rendezVous->getPatient() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $rendezVous->setStatut(RendezVous::STATUT_ANNULE);

        // Libérer le créneau de disponibilité associé si possible
        // Note: Cela suppose qu'on puisse retrouver la disponibilité originale. 
        // Si elle n'est pas liée directement, on pourrait rechercher par date/médecin.
        // Pour l'instant, on change juste le statut du RDV.

        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous annulé avec succès.');
        return $this->redirectToRoute('app_patient_dashboard');
    }

    #[Route('/medecin/confirm/{id}', name: 'app_medecin_confirm_appointment', methods: ['POST'])]
    public function confirm(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le RDV manuellement
        $rendezVous = $entityManager->getRepository(RendezVous::class)->find($id);

        if (!$rendezVous) {
            $this->addFlash('error', 'Rendez-vous non trouvé.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

        // Vérifier que le RDV est pour ce médecin
        if ($rendezVous->getMedecin() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $rendezVous->setStatut(RendezVous::STATUT_CONFIRME);
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous confirmé avec succès.');
        return $this->redirectToRoute('app_medecin_dashboard');
    }

    #[Route('/medecin/reject/{id}', name: 'app_medecin_reject_appointment', methods: ['POST'])]
    public function reject(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le RDV manuellement
        $rendezVous = $entityManager->getRepository(RendezVous::class)->find($id);

        if (!$rendezVous) {
            $this->addFlash('error', 'Rendez-vous non trouvé.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

        // Vérifier que le RDV est pour ce médecin
        if ($rendezVous->getMedecin() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $rendezVous->setStatut(RendezVous::STATUT_ANNULE);
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous refusé.');
        return $this->redirectToRoute('app_medecin_dashboard');
    }

    #[Route('/api/medecin/{id}/disponibilites', name: 'api_medecin_disponibilites', methods: ['GET'])]
    public function getMedecinDisponibilites(int $id, EntityManagerInterface $entityManager): Response
    {
        $medecin = $entityManager->getRepository(\App\Entity\Medecin::class)->find($id);

        if (!$medecin) {
            return $this->json(['error' => 'Médecin non trouvé'], 404);
        }

        // Récupérer les disponibilités futures et libres
        $disponibilites = $entityManager->getRepository(Disponibilite::class)->createQueryBuilder('d')
            ->where('d.medecin = :medecin')
            ->andWhere('d.estDisponible = :disponible')
            ->andWhere('d.dateDebut > :now')
            ->setParameter('medecin', $medecin)
            ->setParameter('disponible', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('d.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($disponibilites as $d) {
            $data[] = [
                'id' => $d->getId(),
                'start' => $d->getDateDebut()->format('Y-m-d H:i'),
                'end' => $d->getDateFin()->format('Y-m-d H:i'),
                'display_date' => $d->getDateDebut()->format('d/m/Y'),
                'display_time' => $d->getDateDebut()->format('H:i') . ' - ' . $d->getDateFin()->format('H:i')
            ];
        }

        return $this->json($data);
    }
}
