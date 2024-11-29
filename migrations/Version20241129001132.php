<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241129001132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to email_verification.user_id foreign key';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing foreign key
        $this->addSql('ALTER TABLE email_verification DROP FOREIGN KEY FK_FE22358A76ED395');
        
        // Add the new foreign key with ON DELETE CASCADE
        $this->addSql('ALTER TABLE email_verification ADD CONSTRAINT FK_FE22358A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop the CASCADE foreign key
        $this->addSql('ALTER TABLE email_verification DROP FOREIGN KEY FK_FE22358A76ED395');
        
        // Restore the original foreign key
        $this->addSql('ALTER TABLE email_verification ADD CONSTRAINT FK_FE22358A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
