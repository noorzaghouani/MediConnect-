<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001165646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');

        $this->addSql(<<<'SQL'
            CREATE TABLE administrateur (
              id INT NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              administrateur
            ADD
              CONSTRAINT `FK_32EB52E8BF396750` FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE consultation (
              id INT AUTO_INCREMENT NOT NULL,
              dossier_medical_id INT NOT NULL,
              observations LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              diagnostic LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              date DATETIME NOT NULL,
              motif LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              symptomes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              allergies LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              tension VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              temperature DOUBLE PRECISION DEFAULT NULL,
              frequence_cardiaque INT DEFAULT NULL,
              medecin_id INT NOT NULL,
              INDEX IDX_964685A67750B79F (dossier_medical_id),
              INDEX IDX_964685A64F31A84 (medecin_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              consultation
            ADD
              CONSTRAINT `FK_964685A64F31A84` FOREIGN KEY (medecin_id) REFERENCES medecin (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              consultation
            ADD
              CONSTRAINT `FK_964685A67750B79F` FOREIGN KEY (dossier_medical_id) REFERENCES dossier_medical (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE disponibilite (
              id INT AUTO_INCREMENT NOT NULL,
              medecin_id INT NOT NULL,
              date_debut DATETIME NOT NULL,
              date_fin DATETIME NOT NULL,
              est_disponible TINYINT NOT NULL,
              INDEX IDX_2CBACE2F4F31A84 (medecin_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              disponibilite
            ADD
              CONSTRAINT `FK_2CBACE2F4F31A84` FOREIGN KEY (medecin_id) REFERENCES medecin (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE dossier_medical (
              id INT AUTO_INCREMENT NOT NULL,
              patient_id INT NOT NULL,
              date_creation DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_3581EE626B899279 (patient_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dossier_medical
            ADD
              CONSTRAINT `FK_3581EE626B899279` FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE medecin (
              id INT NOT NULL,
              diplome VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              specialite_id INT DEFAULT NULL,
              est_verifie TINYINT DEFAULT 0 NOT NULL,
              INDEX IDX_1BDA53C62195E0F0 (specialite_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              medecin
            ADD
              CONSTRAINT `FK_1BDA53C6BF396750` FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              medecin
            ADD
              CONSTRAINT `FK_MEDECIN_SPECIALITE` FOREIGN KEY (specialite_id) REFERENCES speciality (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
              id BIGINT AUTO_INCREMENT NOT NULL,
              body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              headers LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              queue_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              created_at DATETIME NOT NULL,
              available_at DATETIME NOT NULL,
              delivered_at DATETIME DEFAULT NULL,
              INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (
                queue_name, available_at, delivered_at,
                id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE ordonnance (
              id INT AUTO_INCREMENT NOT NULL,
              consultation_id INT DEFAULT NULL,
              contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              created_at DATETIME NOT NULL,
              patient_id INT NOT NULL,
              medecin_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_924B326C62FF6CDF (consultation_id),
              INDEX IDX_924B326C6B899279 (patient_id),
              INDEX IDX_924B326C4F31A84 (medecin_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ordonnance
            ADD
              CONSTRAINT `FK_924B326C4F31A84` FOREIGN KEY (medecin_id) REFERENCES medecin (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ordonnance
            ADD
              CONSTRAINT `FK_924B326C62FF6CDF` FOREIGN KEY (consultation_id) REFERENCES consultation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ordonnance
            ADD
              CONSTRAINT `FK_924B326C6B899279` FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE patient (
              id INT NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              patient
            ADD
              CONSTRAINT `FK_1ADAD7EBBF396750` FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE rendez_vous (
              id INT AUTO_INCREMENT NOT NULL,
              patient_id INT NOT NULL,
              medecin_id INT NOT NULL,
              date_heure DATETIME NOT NULL,
              statut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              motif LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              duree INT DEFAULT 30 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              consultation_id INT DEFAULT NULL,
              INDEX IDX_65E8AA0A6B899279 (patient_id),
              INDEX IDX_65E8AA0A4F31A84 (medecin_id),
              UNIQUE INDEX UNIQ_65E8AA0A62FF6CDF (consultation_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              rendez_vous
            ADD
              CONSTRAINT `FK_65E8AA0A4F31A84` FOREIGN KEY (medecin_id) REFERENCES medecin (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              rendez_vous
            ADD
              CONSTRAINT `FK_65E8AA0A62FF6CDF` FOREIGN KEY (consultation_id) REFERENCES consultation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              rendez_vous
            ADD
              CONSTRAINT `FK_65E8AA0A6B899279` FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE speciality (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
              UNIQUE INDEX UNIQ_F3D7A08E6C6E55B5 (nom),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              roles JSON NOT NULL,
              password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              telephone VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              genre VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              date_naissance DATE NOT NULL,
              created_at DATETIME NOT NULL,
              type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
              UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');

        $this->addSql('DROP TABLE `administrateur`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `consultation`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `disponibilite`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `dossier_medical`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `medecin`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `messenger_messages`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `ordonnance`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `patient`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `rendez_vous`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `speciality`');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDBPlatform'."
        );

        $this->addSql('DROP TABLE `users`');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
