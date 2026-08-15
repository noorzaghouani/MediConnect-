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
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

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

    #[Route('/medecin/ordonnance/new', name: 'app_medecin_ordonnance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        // Récupérer SEULEMENT les patients qui ont au moins une consultation avec CE médecin
        $patients = $em->getRepository(Patient::class)->findPatientsWithConsultationsByMedecin($medecin);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ordonnance_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_medecin_ordonnances');
            }

            $patientId = $request->request->get('patient_id');
            $contenu = $request->request->get('contenu');

            $patient = $em->getRepository(Patient::class)->find($patientId);

            if (!$patient) {
                $this->addFlash('error', 'Patient non trouvé.');
                return $this->redirectToRoute('app_medecin_ordonnance_new');
            }

            $patientsAutorises = $em->getRepository(Patient::class)->findPatientsWithConsultationsByMedecin($medecin);
            $patientsAutoriseIds = array_map(fn($p) => $p->getId(), $patientsAutorises);
            if (!in_array($patient->getId(), $patientsAutoriseIds, true)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas créer une ordonnance pour ce patient.');
            }

            $ordonnance = new Ordonnance();
            $ordonnance->setPatient($patient);
            $ordonnance->setMedecin($medecin);
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
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        if ($ordonnance->getMedecin() !== $medecin) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette ordonnance.');
        }

        return $this->render('medecin/ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance
        ]);
    }

    #[Route('/medecin/ordonnance/{id}/edit', name: 'app_medecin_ordonnance_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->render('medecin/pending_verification.html.twig');
        }

        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        if ($ordonnance->getMedecin() !== $medecin) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette ordonnance.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ordonnance_edit_' . $id, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_medecin_ordonnance_edit', ['id' => $id]);
            }

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
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Medecin $medecin */
        $medecin = $this->getUser();

        if (!$medecin->isEstVerifie()) {
            return $this->redirectToRoute('app_medecin_dashboard');
        }

        $ordonnance = $em->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            $this->addFlash('error', 'Ordonnance non trouvée.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        if ($ordonnance->getMedecin() !== $medecin) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette ordonnance.');
        }

        if (!$this->isCsrfTokenValid('ordonnance_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_medecin_ordonnances');
        }

        $em->remove($ordonnance);
        $em->flush();

        $this->addFlash('success', 'Ordonnance supprimée avec succès.');
        return $this->redirectToRoute('app_medecin_ordonnances');
    }
}
