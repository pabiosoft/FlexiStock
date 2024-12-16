<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241215160044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, equipment_id INT DEFAULT NULL, level VARCHAR(20) NOT NULL, message VARCHAR(255) NOT NULL, priority VARCHAR(50) NOT NULL, persistent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, category VARCHAR(50) DEFAULT NULL, INDEX IDX_17FD46C1517FE9FE (equipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, category_order INT DEFAULT 0 NOT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_verification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(100) NOT NULL, expires_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_FE22358A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, assigned_user_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, brand VARCHAR(100) DEFAULT NULL, model VARCHAR(100) DEFAULT NULL, serial_number VARCHAR(100) NOT NULL, purchase_date DATE DEFAULT NULL, warranty_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, stock_quantity INT DEFAULT 0 NOT NULL, description LONGTEXT DEFAULT NULL, reserved_quantity INT DEFAULT 0 NOT NULL, min_threshold INT DEFAULT 1 NOT NULL, price NUMERIC(10, 2) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, last_maintenance_date DATETIME DEFAULT NULL, next_maintenance_date DATETIME DEFAULT NULL, expiration_date DATETIME DEFAULT NULL, low_stock_threshold INT DEFAULT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_D338D583D948EE2 (serial_number), UNIQUE INDEX UNIQ_D338D583989D9B62 (slug), INDEX IDX_D338D58312469DE2 (category_id), INDEX IDX_D338D583ADF66B1A (assigned_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipment_subcategories (equipment_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_57744E9517FE9FE (equipment_id), INDEX IDX_57744E912469DE2 (category_id), PRIMARY KEY(equipment_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE images (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_E01FBE6A517FE9FE (equipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_record (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, performed_by_id INT NOT NULL, maintenance_date DATETIME NOT NULL, maintenance_type VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, cost NUMERIC(10, 2) DEFAULT NULL, next_maintenance_date DATETIME NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_B1C9A998517FE9FE (equipment_id), INDEX IDX_B1C9A9982E65C292 (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, user_id INT DEFAULT NULL, category_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, quantity INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, movement_date DATETIME NOT NULL, created_at DATETIME NOT NULL, reference VARCHAR(255) DEFAULT NULL, details JSON DEFAULT NULL, location VARCHAR(50) DEFAULT NULL, batch_number VARCHAR(50) DEFAULT NULL, expiry_date DATETIME DEFAULT NULL, INDEX IDX_F4DD95F7517FE9FE (equipment_id), INDEX IDX_F4DD95F7A76ED395 (user_id), INDEX IDX_F4DD95F712469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_request_id INT NOT NULL, equipment_id INT NOT NULL, quantity INT NOT NULL, unit_price DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, INDEX IDX_52EA1F09F1A445F0 (order_request_id), INDEX IDX_52EA1F09517FE9FE (equipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_request (id INT AUTO_INCREMENT NOT NULL, supplier_id INT DEFAULT NULL, customer_id INT NOT NULL, order_date DATETIME NOT NULL, status VARCHAR(20) NOT NULL, payment_status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, total_price DOUBLE PRECISION NOT NULL, vat_rate DOUBLE PRECISION NOT NULL, vat_amount DOUBLE PRECISION NOT NULL, subtotal DOUBLE PRECISION NOT NULL, received_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, validated_at DATETIME DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, completed_at DATETIME DEFAULT NULL, INDEX IDX_CDED26D42ADD6D8C (supplier_id), INDEX IDX_CDED26D49395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE request (id INT AUTO_INCREMENT NOT NULL, requester_id INT NOT NULL, requested_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_3B978F9FED442CF4 (requester_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE request_item (id INT AUTO_INCREMENT NOT NULL, request_id INT NOT NULL, equipment_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_60BC02E4427EB8A5 (request_id), INDEX IDX_60BC02E4517FE9FE (equipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, user_id INT DEFAULT NULL, reserved_quantity INT NOT NULL, status VARCHAR(20) NOT NULL, reservation_date DATETIME NOT NULL, return_date DATETIME DEFAULT NULL, INDEX IDX_42C84955517FE9FE (equipment_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, contact VARCHAR(100) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, address LONGTEXT DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, image VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_verification ADD CONSTRAINT FK_FE22358A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D58312469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D583ADF66B1A FOREIGN KEY (assigned_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE equipment_subcategories ADD CONSTRAINT FK_57744E9517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipment_subcategories ADD CONSTRAINT FK_57744E912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE images ADD CONSTRAINT FK_E01FBE6A517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE maintenance_record ADD CONSTRAINT FK_B1C9A998517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE maintenance_record ADD CONSTRAINT FK_B1C9A9982E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09F1A445F0 FOREIGN KEY (order_request_id) REFERENCES order_request (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE order_request ADD CONSTRAINT FK_CDED26D42ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE order_request ADD CONSTRAINT FK_CDED26D49395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9FED442CF4 FOREIGN KEY (requester_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE request_item ADD CONSTRAINT FK_60BC02E4427EB8A5 FOREIGN KEY (request_id) REFERENCES request (id)');
        $this->addSql('ALTER TABLE request_item ADD CONSTRAINT FK_60BC02E4517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1517FE9FE');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE email_verification DROP FOREIGN KEY FK_FE22358A76ED395');
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D58312469DE2');
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D583ADF66B1A');
        $this->addSql('ALTER TABLE equipment_subcategories DROP FOREIGN KEY FK_57744E9517FE9FE');
        $this->addSql('ALTER TABLE equipment_subcategories DROP FOREIGN KEY FK_57744E912469DE2');
        $this->addSql('ALTER TABLE images DROP FOREIGN KEY FK_E01FBE6A517FE9FE');
        $this->addSql('ALTER TABLE maintenance_record DROP FOREIGN KEY FK_B1C9A998517FE9FE');
        $this->addSql('ALTER TABLE maintenance_record DROP FOREIGN KEY FK_B1C9A9982E65C292');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7517FE9FE');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7A76ED395');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F712469DE2');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09F1A445F0');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09517FE9FE');
        $this->addSql('ALTER TABLE order_request DROP FOREIGN KEY FK_CDED26D42ADD6D8C');
        $this->addSql('ALTER TABLE order_request DROP FOREIGN KEY FK_CDED26D49395C3F3');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FED442CF4');
        $this->addSql('ALTER TABLE request_item DROP FOREIGN KEY FK_60BC02E4427EB8A5');
        $this->addSql('ALTER TABLE request_item DROP FOREIGN KEY FK_60BC02E4517FE9FE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955517FE9FE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('DROP TABLE alert');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE email_verification');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE equipment_subcategories');
        $this->addSql('DROP TABLE images');
        $this->addSql('DROP TABLE maintenance_record');
        $this->addSql('DROP TABLE movement');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE order_request');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE request');
        $this->addSql('DROP TABLE request_item');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
