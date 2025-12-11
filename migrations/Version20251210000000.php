<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Speciality entity and migrate data from Medecin';
    }

    public function up(Schema $schema): void
    {
        // Create table
        $this->addSql('CREATE TABLE speciality (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_SPECIALITY_NOM (nom), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add relation column
        $this->addSql('ALTER TABLE medecin ADD specialite_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT FK_MEDECIN_SPECIALITE FOREIGN KEY (specialite_id) REFERENCES speciality (id)');
        $this->addSql('CREATE INDEX IDX_MEDECIN_SPECIALITE ON medecin (specialite_id)');

        // Migrate Data
        $this->addSql('INSERT INTO speciality (nom) SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite <> ""');
        $this->addSql('UPDATE medecin m JOIN speciality s ON m.specialite = s.nom SET m.specialite_id = s.id');

        // Drop old column
        $this->addSql('ALTER TABLE medecin DROP specialite');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE medecin ADD specialite VARCHAR(255) DEFAULT NULL');
        // Restore data
        $this->addSql('UPDATE medecin m JOIN speciality s ON m.specialite_id = s.id SET m.specialite = s.nom');

        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY FK_MEDECIN_SPECIALITE');
        $this->addSql('DROP INDEX IDX_MEDECIN_SPECIALITE ON medecin');
        $this->addSql('ALTER TABLE medecin DROP specialite_id');
        $this->addSql('DROP TABLE speciality');
    }
}
