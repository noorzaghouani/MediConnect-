<?php

namespace App\Command;

use App\Entity\Administrateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur',
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

        $io->title('Création du compte administrateur MediConnect');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $emailQuestion = new Question('Email de l\'administrateur : ');
        $emailQuestion->setValidator(function ($value) {
            if (empty(trim($value))) {
                throw new \RuntimeException('L\'email ne peut pas être vide.');
            }
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email invalide.');
            }
            return $value;
        });
        $email = $helper->ask($input, $output, $emailQuestion);

        $nomQuestion = new Question('Nom : ');
        $nomQuestion->setValidator(fn($v) => !empty(trim($v)) ? $v : throw new \RuntimeException('Le nom est requis.'));
        $nom = $helper->ask($input, $output, $nomQuestion);

        $prenomQuestion = new Question('Prénom : ');
        $prenomQuestion->setValidator(fn($v) => !empty(trim($v)) ? $v : throw new \RuntimeException('Le prénom est requis.'));
        $prenom = $helper->ask($input, $output, $prenomQuestion);

        $telephoneQuestion = new Question('Téléphone (format international ex: +33612345678) : ');
        $telephone = $helper->ask($input, $output, $telephoneQuestion);

        $passwordQuestion = new Question('Mot de passe (min. 12 caractères) : ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function ($value) {
            if (strlen($value) < 12) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 12 caractères.');
            }
            return $value;
        });
        $plainPassword = $helper->ask($input, $output, $passwordQuestion);

        $confirmQuestion = new Question('Confirmer le mot de passe : ');
        $confirmQuestion->setHidden(true);
        $confirmQuestion->setHiddenFallback(false);
        $confirm = $helper->ask($input, $output, $confirmQuestion);

        if ($plainPassword !== $confirm) {
            $io->error('Les mots de passe ne correspondent pas.');
            return Command::FAILURE;
        }

        // Créer l'administrateur
        $admin = new Administrateur();
        $admin->setEmail($email);
        $admin->setNom($nom);
        $admin->setPrenom($prenom);
        $admin->setTelephone($telephone ?? '+33000000000');
        $admin->setGenre('homme');
        $admin->setDateNaissance(new \DateTime('1990-01-01'));

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrateur créé avec succès !');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Email', $email],
                ['Nom', $nom . ' ' . $prenom],
                ['Rôle', 'ROLE_ADMIN']
            ]
        );

        return Command::SUCCESS;
    }
}
