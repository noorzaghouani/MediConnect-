<?php

namespace App\Controller;

use App\Entity\Ordonnance;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrdonnanceController extends AbstractController
{
    #[Route('/medecin/ordonnances', name: 'app_medecin_ordonnances')]
    public function index(EntityManagerInterface $em): Response
    {
        $medecin = $this->getUser();

        // Récupérer toutes les ordonnances créées via les consultations du médecin OU directement par le médecin
        $ordonnances = $em->getRepository(Ordonnance::class)->createQueryBuilder('o')
            ->leftJoin('o.consultation', 'c')
            ->where('c.medecin = :medecin OR o.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('medecin/ordonnance/index.html.twig', [
            'ordonnances' => $ordonnances
        ]);
    }

    #[Route('/medecin/ordonnance/new', name: 'app_medecin_ordonnance_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $medecin = $this->getUser();

        // Récupérer SEULEMENT les patients qui ont au moins une consultation
        $patients = $em->getRepository(Patient::class)->findPatientsWithConsultationsByMedecin($medecin);

        if ($request->isMethod('POST')) {
            $patientId = $request->request->get('patient_id');
            $contenu = $request->request->get('contenu');

            $patient = $em->getRepository(Patient::class)->find($patientId);

            if (!$patient) {
                $this->addFlash('error', 'Patient non trouvé.');
                return $this->redirectToRoute('app_medecin_ordonnance_new');
            }

            $ordonnance = new Ordonnance();
            $ordonnance->setPatient($patient);
            $ordonnance->setMedecin($medecin); // Lier le médecin
            $ordonnance->setContenu($contenu);

            $em->persist($ordonnance);
            $em->flush();

            $this->addFlash('success', 'Ordonnance créée avec succès.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        return $this->render('medecin/ordonnance/new.html.twig', [
            'patients' => $patients
        ]);
    }

    #[Route('/medecin/ordonnance/{id}', name: 'app_medecin_ordonnance_show')]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        return $this->render('medecin/ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance
        ]);
    }

    #[Route('/medecin/ordonnance/{id}/edit', name: 'app_medecin_ordonnance_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            $ordonnance->setContenu($contenu);

            $em->flush();

            $this->addFlash('success', 'Ordonnance modifiée avec succès.');
            return $this->redirectToRoute('app_medecin_ordonnance_show', ['id' => $ordonnance->getId()]);
        }

        return $this->render('medecin/ordonnance/edit.html.twig', [
            'ordonnance' => $ordonnance
        ]);
    }

    #[Route('/medecin/ordonnance/{id}/delete', name: 'app_medecin_ordonnance_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        $em->remove($ordonnance);
        $em->flush();

        $this->addFlash('success', 'Ordonnance supprimée avec succès.');
        return $this->redirectToRoute('app_medecin_ordonnances');
    }
}
