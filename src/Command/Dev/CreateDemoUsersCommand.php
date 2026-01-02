<?php

namespace App\Command\Dev;

use App\Entity\Administrateur;
use App\Entity\Medecin;
use App\Entity\Speciality;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-demo-users',
    description: 'Creates demo admin and doctor users',
)]
class CreateDemoUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Création de l'Administrateur
        $adminEmail = 'admin@mediconnect.com';
        $existingAdmin = $this->entityManager->getRepository(Administrateur::class)->findOneBy(['email' => $adminEmail]);

        if (!$existingAdmin) {
            $admin = new Administrateur();
            $admin->setEmail($adminEmail);
            $admin->setNom('Admin');
            $admin->setPrenom('System');
            $admin->setTelephone('0100000000');
            $admin->setGenre('Autre');
            $admin->setDateNaissance(new \DateTime('1980-01-01'));
            // Le constructeur de Administrateur met déjà ROLE_ADMIN, mais on s'assure
            $admin->setRoles(['ROLE_ADMIN']);

            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);

            $this->entityManager->persist($admin);
            $output->writeln("✅ Admin créé: $adminEmail / admin123");
        } else {
            $output->writeln("ℹ️  Admin existe déjà.");
        }

        // 2. Création du Médecin
        $medecinEmail = 'medecin@mediconnect.com';
        $existingMedecin = $this->entityManager->getRepository(Medecin::class)->findOneBy(['email' => $medecinEmail]);

        if (!$existingMedecin) {
            $medecin = new Medecin();
            $medecin->setEmail($medecinEmail);
            $medecin->setNom('Dr. House');
            $medecin->setPrenom('Gregory');
            $medecin->setTelephone('0612345678');
            $medecin->setGenre('Homme');
            $medecin->setDateNaissance(new \DateTime('1975-05-15'));
            $medecin->setDiplome('Doctorat en Médecine Interne');
            $medecin->setEstVerifie(true); // Important pour qu'il puisse se connecter
            $medecin->setRoles(['ROLE_MEDECIN']);

            // Assigner une spécialité
            $speciality = $this->entityManager->getRepository(Speciality::class)->findOneBy(['nom' => 'Médecine générale']);
            if ($speciality) {
                $medecin->setSpecialite($speciality);
            }

            $hashedPassword = $this->passwordHasher->hashPassword($medecin, 'medecin123');
            $medecin->setPassword($hashedPassword);

            $this->entityManager->persist($medecin);
            $output->writeln("✅ Médecin créé: $medecinEmail / medecin123");
        } else {
            $output->writeln("ℹ️  Médecin existe déjà.");
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}
