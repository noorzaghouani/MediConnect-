<?php

namespace App\Command;

use App\Entity\Disponibilite;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-disponibilites',
    description: 'Ajouter des disponibilités de test pour tous les médecins',
)]
class SeedDisponibilitesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MedecinRepository $medecinRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les médecins
        $medecins = $this->medecinRepository->findAll();

        if (empty($medecins)) {
            $io->error('Aucun médecin trouvé dans la base de données.');
            return Command::FAILURE;
        }

        $count = 0;

        foreach ($medecins as $medecin) {
            // Créer des disponibilités pour les 7 prochains jours
            for ($day = 1; $day <= 7; $day++) {
                $date = new \DateTime("+{$day} days");

                // Matin: 9h - 12h
                $matinDebut = clone $date;
                $matinDebut->setTime(9, 0);
                $matinFin = clone $date;
                $matinFin->setTime(12, 0);

                $dispoMatin = new Disponibilite();
                $dispoMatin->setMedecin($medecin);
                $dispoMatin->setDateDebut($matinDebut);
                $dispoMatin->setDateFin($matinFin);
                $dispoMatin->setEstDisponible(true);

                $this->entityManager->persist($dispoMatin);
                $count++;

                // Après-midi: 14h - 18h
                $apremDebut = clone $date;
                $apremDebut->setTime(14, 0);
                $apremFin = clone $date;
                $apremFin->setTime(18, 0);

                $dispoAprem = new Disponibilite();
                $dispoAprem->setMedecin($medecin);
                $dispoAprem->setDateDebut($apremDebut);
                $dispoAprem->setDateFin($apremFin);
                $dispoAprem->setEstDisponible(true);

                $this->entityManager->persist($dispoAprem);
                $count++;
            }
        }

        $this->entityManager->flush();

        $io->success("{$count} disponibilités créées pour " . count($medecins) . " médecin(s).");

        return Command::SUCCESS;
    }
}
