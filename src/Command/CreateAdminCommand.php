<?php

namespace App\Command;

use App\Entity\Administrateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur par défaut',
)]
class CreateAdminCommand extends Command
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

        // Vérifier si un admin existe déjà
        $existingAdmin = $this->entityManager->getRepository(Administrateur::class)->findOneBy([]);

        if ($existingAdmin) {
            $io->warning('Un administrateur existe déjà dans la base de données.');
            return Command::SUCCESS;
        }

        // Créer un nouvel administrateur
        $admin = new Administrateur();
        $admin->setEmail('admin@mediconnect.com');
        $admin->setNom('Admin');
        $admin->setPrenom('MediConnect');
        $admin->setTelephone('+21612345678');
        $admin->setGenre('homme');
        $admin->setDateNaissance(new \DateTime('1990-01-01'));

        // Hash le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123' // Mot de passe par défaut
        );
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrateur créé avec succès !');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Email', 'admin@mediconnect.com'],
                ['Mot de passe', 'admin123'],
                ['Rôle', 'ROLE_ADMIN']
            ]
        );

        $io->warning('IMPORTANT: Changez le mot de passe après la première connexion !');

        return Command::SUCCESS;
    }
}
