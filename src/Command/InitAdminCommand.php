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
    name: 'app:init-admin',
    description: 'Initialise le compte administrateur par défaut',
)]
class InitAdminCommand extends Command
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

        $existingAdmin = $this->entityManager->getRepository(Administrateur::class)->findOneBy([]);

        if ($existingAdmin) {
            $io->info('Un administrateur existe déjà dans la base de données.');
            return Command::SUCCESS;
        }

        $email = $_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? 'admin@mediconnect.com';
        $plainPassword = $_ENV['ADMIN_PASSWORD'] ?? $_SERVER['ADMIN_PASSWORD'] ?? 'Admin123456!';

        $admin = new Administrateur();
        $admin->setEmail($email);
        $admin->setNom('Admin');
        $admin->setPrenom('Principal');
        $admin->setTelephone('+21620000000');
        $admin->setGenre('homme');
        $admin->setDateNaissance(new \DateTime('1990-01-01'));
        $admin->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Compte administrateur initialisé : %s', $email));

        return Command::SUCCESS;
    }
}
