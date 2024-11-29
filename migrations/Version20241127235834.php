<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127235834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE maintenance_record (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, performed_by_id INT NOT NULL, maintenance_date DATETIME NOT NULL, maintenance_type VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, cost NUMERIC(10, 2) DEFAULT NULL, next_maintenance_date DATETIME NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_B1C9A998517FE9FE (equipment_id), INDEX IDX_B1C9A9982E65C292 (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE maintenance_record ADD CONSTRAINT FK_B1C9A998517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE maintenance_record ADD CONSTRAINT FK_B1C9A9982E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE equipment ADD last_maintenance_date DATETIME DEFAULT NULL, ADD next_maintenance_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maintenance_record DROP FOREIGN KEY FK_B1C9A998517FE9FE');
        $this->addSql('ALTER TABLE maintenance_record DROP FOREIGN KEY FK_B1C9A9982E65C292');
        $this->addSql('DROP TABLE maintenance_record');
        $this->addSql('ALTER TABLE equipment DROP last_maintenance_date, DROP next_maintenance_date');
    }
}
