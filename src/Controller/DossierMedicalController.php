<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Entity\Patient;
use App\Entity\RendezVous;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierMedicalController extends AbstractController
{
    #[Route('/medecin/dossier/{id}', name: 'app_dossier_show')]
    public function show(Patient $patient, EntityManagerInterface $em): Response
    {
        $dossier = $patient->getDossierMedical();

        // Si le dossier n'existe pas, on le crée (cas pour les anciens patients)
        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $em->persist($dossier);
            $em->flush();
        }

        return $this->render('medecin/dossier/show.html.twig', [
            'patient' => $patient,
            'dossier' => $dossier
        ]);
    }

    #[Route('/medecin/consultation/new/{id}', name: 'app_consultation_new')]
    public function createConsultation(RendezVous $rendezVous, Request $request, EntityManagerInterface $em): Response
    {
        $patient = $rendezVous->getPatient();
        $dossier = $patient->getDossierMedical();

        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $em->persist($dossier);
        }

        if ($request->isMethod('POST')) {
            $consultation = new Consultation();
            $consultation->setDossierMedical($dossier);
            $consultation->setMedecin($this->getUser());
            $consultation->setRendezVous($rendezVous);
            $consultation->setMotif($request->request->get('motif'));
            $consultation->setDiagnostic($request->request->get('diagnostic'));
            $consultation->setTraitement($request->request->get('traitement'));
            $consultation->setObservations($request->request->get('observations'));
            $consultation->setDate(new \DateTime());

            // Marquer le rendez-vous comme terminé
            $rendezVous->setStatut(RendezVous::STATUT_TERMINE);

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'La consultation a été enregistrée avec succès.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

        return $this->render('medecin/consultation/new.html.twig', [
            'patient' => $patient,
            'rendezVous' => $rendezVous
        ]);
    }
}
