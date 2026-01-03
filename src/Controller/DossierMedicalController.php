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
    public function show(int $id, Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer le patient manuellement
        $patient = $em->getRepository(Patient::class)->find($id);

        // Déterminer la page de retour (dashboard ou patients)
        $from = $request->query->get('from', 'patients');
        $returnRoute = $from === 'dashboard' ? 'app_medecin_dashboard' : 'app_medecin_patients';

        if (!$patient) {
            $this->addFlash('error', 'Patient non trouvé.');
            return $this->redirectToRoute($returnRoute);
        }

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
            'dossier' => $dossier,
            'returnRoute' => $returnRoute
        ]);
    }

    #[Route('/medecin/consultation/new/{id}', name: 'app_consultation_new')]
    public function createConsultation(int $id, Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer le RDV manuellement
        $rendezVous = $em->getRepository(RendezVous::class)->find($id);

        if (!$rendezVous) {
            $this->addFlash('error', 'Rendez-vous non trouvé.');
            return $this->redirectToRoute('app_medecin_dashboard');
        }

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
            $consultation->setSymptomes($request->request->get('symptomes'));
            $consultation->setAllergies($request->request->get('allergies'));
            $consultation->setDiagnostic($request->request->get('diagnostic'));
            $consultation->setObservations($request->request->get('observations'));

            // Constantes vitales
            $consultation->setTension($request->request->get('tension'));

            // Validation température
            $temperature = $request->request->get('temperature');
            if ($temperature) {
                $temp = (float) $temperature;
                if ($temp < 30 || $temp > 45) {
                    $this->addFlash('error', 'La température doit être entre 30° et 45°C');
                    return $this->render('medecin/consultation/new.html.twig', [
                        'patient' => $patient,
                        'rendezVous' => $rendezVous
                    ]);
                }
                $consultation->setTemperature($temp);
            }

            // Validation fréquence cardiaque
            $frequenceCardiaque = $request->request->get('frequence_cardiaque');
            if ($frequenceCardiaque) {
                $fc = (int) $frequenceCardiaque;
                if ($fc < 40 || $fc > 200) {
                    $this->addFlash('error', 'La fréquence cardiaque doit être entre 40 et 200 bpm');
                    return $this->render('medecin/consultation/new.html.twig', [
                        'patient' => $patient,
                        'rendezVous' => $rendezVous
                    ]);
                }
                $consultation->setFrequenceCardiaque($fc);
            }

            $consultation->setDate(new \DateTime());

            // Marquer le rendez-vous comme terminé
            $rendezVous->setStatut(RendezVous::STATUT_TERMINE);

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'La consultation a été enregistrée avec succès.');
            return $this->redirectToRoute('app_medecin_patients');
        }

        return $this->render('medecin/consultation/new.html.twig', [
            'patient' => $patient,
            'rendezVous' => $rendezVous
        ]);
    }
}
