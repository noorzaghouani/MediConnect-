<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
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

        if (!$this->isCsrfTokenValid('book_rdv_js', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

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
        $rdv->setStatut(RendezVous::STATUT_ATTENTE);

        // Calculer la durée en minutes (entier)
        $diff = $disponibilite->getDateFin()->getTimestamp() - $disponibilite->getDateDebut()->getTimestamp();
        $rdv->setDuree((int) ($diff / 60));

        // 3. Marquer le créneau comme indisponible
        $disponibilite->setEstDisponible(false);

        // 4. Sauvegarder
        $entityManager->persist($rdv);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée. En attente de confirmation.');

        $response = $this->redirectToRoute('app_patient_dashboard');

        $isSecure = $request->isSecure();
        $cookie = Cookie::create(
            'last_doctor_id',
            $disponibilite->getMedecin()->getId(),
            time() + (3600 * 24 * 30),
            '/',
            null,
            $isSecure,   // secure : true uniquement en HTTPS
            true,        // httpOnly : inaccessible au JS
            false,
            Cookie::SAMESITE_LAX
        );

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/patient/cancel/{id}', name: 'app_patient_cancel_appointment', methods: ['POST'])]
    public function cancel(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();

        if (!$this->isCsrfTokenValid('cancel_rdv_js', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

        $rendezVous = $entityManager->getRepository(RendezVous::class)->find($id);

        if (!$rendezVous) {
            $this->addFlash('error', 'Rendez-vous non trouvé.');
            return $this->redirectToRoute('app_patient_dashboard');
        }

        // Vérifier que le RDV appartient bien au patient connecté
        if ($rendezVous->getPatient() !== $patient) {
            throw $this->createAccessDeniedException();
        }

        $rendezVous->setStatut(RendezVous::STATUT_ANNULE);

        // Rechercher la disponibilité par médecin + date de début
        $disponibilite = $entityManager->getRepository(Disponibilite::class)->findOneBy([
            'medecin'   => $rendezVous->getMedecin(),
            'dateDebut' => $rendezVous->getDateHeure(),
        ]);

        if ($disponibilite) {
            $disponibilite->setEstDisponible(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous annulé avec succès.');
        return $this->redirectToRoute('app_patient_dashboard');
    }

    #[Route('/medecin/confirm/{id}', name: 'app_medecin_confirm_appointment', methods: ['POST'])]
    public function confirm(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('confirm_rdv_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

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
    public function reject(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reject_rdv_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

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

        $disponibilite = $entityManager->getRepository(Disponibilite::class)->findOneBy([
            'medecin'   => $rendezVous->getMedecin(),
            'dateDebut' => $rendezVous->getDateHeure(),
        ]);
        if ($disponibilite) {
            $disponibilite->setEstDisponible(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous refusé.');
        return $this->redirectToRoute('app_medecin_dashboard');
    }

    #[Route('/api/medecin/{id}/disponibilites', name: 'api_medecin_disponibilites', methods: ['GET'])]
    public function getMedecinDisponibilites(int $id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $medecin = $entityManager->getRepository(\App\Entity\Medecin::class)->find($id);

        if (!$medecin) {
            return $this->json(['error' => 'Médecin non trouvé'], 404);
        }

        if (!$medecin->isEstVerifie()) {
            return $this->json(['error' => 'Médecin non disponible'], 403);
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
                'id'           => $d->getId(),
                'start'        => $d->getDateDebut()->format('Y-m-d H:i'),
                'end'          => $d->getDateFin()->format('Y-m-d H:i'),
                'display_date' => $d->getDateDebut()->format('d/m/Y'),
                'display_time' => $d->getDateDebut()->format('H:i') . ' - ' . $d->getDateFin()->format('H:i')
            ];
        }

        return $this->json($data);
    }
}
