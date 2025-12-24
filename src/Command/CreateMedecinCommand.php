<?php

namespace App\Command;

use App\Entity\Medecin;
use App\Entity\Speciality;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-medecin',
    description: 'Créer un compte médecin de test',
)]
class CreateMedecinCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if speciality exists or create one
        $speciality = $this->entityManager->getRepository(Speciality::class)->findOneBy(['nom' => 'Généraliste']);
        if (!$speciality) {
            $speciality = new Speciality();
            $speciality->setNom('Généraliste');
            $speciality->setDescription('Médecine générale');
            $this->entityManager->persist($speciality);
        }

        $medecin = new Medecin();
        $medecin->setEmail('medecin@test.com');
        $medecin->setNom('Docteur');
        $medecin->setPrenom('Test');
        $medecin->setTelephone('0123456789');
        $medecin->setGenre('homme');
        $medecin->setDateNaissance(new \DateTime('1980-01-01'));
        $medecin->setSpecialite($speciality);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $medecin,
            'medecin123'
        );
        $medecin->setPassword($hashedPassword);

        $this->entityManager->persist($medecin);
        $this->entityManager->flush();

        $io->success('Médecin de test créé avec succès !');
        $io->table(
            ['Email', 'Mot de passe'],
            [['medecin@test.com', 'medecin123']]
        );

        return Command::SUCCESS;
    }
}
