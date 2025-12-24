<?php

namespace App\Command;

use App\Entity\Speciality;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-specialities',
    description: 'Charger une liste de spécialités médicales dans la base de données',
)]
class LoadSpecialitiesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $specialities = [
            'Allergologie' => 'Étude des allergies et de leurs traitements.',
            'Anesthésiologie' => 'Gestion de l\'anesthésie et de la douleur.',
            'Cardiologie' => 'Maladies du cœur et des vaisseaux sanguins.',
            'Chirurgie générale' => 'Opérations chirurgicales sur l\'abdomen, les seins, etc.',
            'Dermatologie' => 'Maladies de la peau, des ongles et des cheveux.',
            'Endocrinologie' => 'Troubles hormonaux et maladies métaboliques.',
            'Gastro-entérologie' => 'Maladies du système digestif.',
            'Gériatrie' => 'Médecine des personnes âgées.',
            'Gynécologie' => 'Santé de l\'appareil reproducteur féminin.',
            'Hématologie' => 'Maladies du sang.',
            'Infectiologie' => 'Maladies infectieuses et tropicales.',
            'Médecine générale' => 'Soins de santé primaires et suivi global.',
            'Médecine interne' => 'Maladies systémiques et complexes.',
            'Néphrologie' => 'Maladies des reins.',
            'Neurologie' => 'Maladies du système nerveux.',
            'Oncologie' => 'Diagnostic et traitement des cancers.',
            'Ophtalmologie' => 'Maladies des yeux et de la vision.',
            'Orthopédie' => 'Maladies de l\'appareil locomoteur (os, articulations).',
            'Oto-rhino-laryngologie (ORL)' => 'Oreilles, nez et gorge.',
            'Pédiatrie' => 'Médecine des enfants et adolescents.',
            'Pneumologie' => 'Maladies des poumons et des voies respiratoires.',
            'Psychiatrie' => 'Troubles mentaux et comportementaux.',
            'Radiologie' => 'Imagerie médicale (rayons X, IRM, etc.).',
            'Rhumatologie' => 'Maladies des os, articulations et muscles.',
            'Urologie' => 'Appareil urinaire et appareil reproducteur masculin.'
        ];

        $repo = $this->entityManager->getRepository(Speciality::class);
        $count = 0;

        foreach ($specialities as $nom => $description) {
            $existing = $repo->findOneBy(['nom' => $nom]);

            if (!$existing) {
                $speciality = new Speciality();
                $speciality->setNom($nom);
                $speciality->setDescription($description);
                $this->entityManager->persist($speciality);
                $count++;
            }
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success(sprintf('%d spécialités ont été ajoutées avec succès.', $count));
        } else {
            $io->info('Toutes les spécialités existent déjà.');
        }

        return Command::SUCCESS;
    }
}
