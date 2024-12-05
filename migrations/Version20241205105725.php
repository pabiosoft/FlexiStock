<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205105725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add equipment relation and category to Alert entity';
    }

    public function up(Schema $schema): void
    {
        // Check if columns exist before adding them
        $columns = $this->connection->fetchAllAssociative("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = 'flexistock3' 
            AND TABLE_NAME = 'alert'
        ");
        $existingColumns = array_column($columns, 'COLUMN_NAME');

        // Add read_at column if it doesn't exist
        if (!in_array('read_at', $existingColumns)) {
            $this->addSql('ALTER TABLE alert ADD read_at DATETIME DEFAULT NULL');
        }
        
        // Add equipment_id column if it doesn't exist
        if (!in_array('equipment_id', $existingColumns)) {
            $this->addSql('ALTER TABLE alert ADD equipment_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
            $this->addSql('CREATE INDEX IDX_17FD46C1517FE9FE ON alert (equipment_id)');
        }
        
        // Add category column if it doesn't exist
        if (!in_array('category', $existingColumns)) {
            $this->addSql('ALTER TABLE alert ADD category VARCHAR(50) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if columns exist before removing them
        $columns = $this->connection->fetchAllAssociative("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = 'flexistock3' 
            AND TABLE_NAME = 'alert'
        ");
        $existingColumns = array_column($columns, 'COLUMN_NAME');

        // Remove category column if it exists
        if (in_array('category', $existingColumns)) {
            $this->addSql('ALTER TABLE alert DROP category');
        }
        
        // Remove equipment relation if it exists
        if (in_array('equipment_id', $existingColumns)) {
            $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1517FE9FE');
            $this->addSql('DROP INDEX IDX_17FD46C1517FE9FE ON alert');
            $this->addSql('ALTER TABLE alert DROP equipment_id');
        }
        
        // Remove read_at column if it exists
        if (in_array('read_at', $existingColumns)) {
            $this->addSql('ALTER TABLE alert DROP read_at');
        }
    }
}
