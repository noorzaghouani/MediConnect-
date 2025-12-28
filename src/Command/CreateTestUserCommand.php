<?php

namespace App\Command;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test patient user',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'test@patient.com';

        $existing = $this->entityManager->getRepository(Patient::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $output->writeln("L'utilisateur $email existe déjà.");
            return Command::SUCCESS;
        }

        $patient = new Patient();
        $patient->setEmail($email);
        $patient->setNom('Test');
        $patient->setPrenom('Patient');
        $patient->setTelephone('0600000000');
        $patient->setGenre('Homme');
        $patient->setDateNaissance(new \DateTime('1990-01-01'));
        $patient->setRoles(['ROLE_PATIENT']);

        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'password123');
        $patient->setPassword($hashedPassword);

        $this->entityManager->persist($patient);
        $this->entityManager->flush();

        $output->writeln("Utilisateur créé avec succès !");
        $output->writeln("Email: $email");
        $output->writeln("Password: password123");
        $output->writeln("Hash: $hashedPassword");

        return Command::SUCCESS;
    }
}
