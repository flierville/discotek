<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261023194632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial du site Discotek (pages, devis, contacts, soirées, newsletter, administrateurs)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, search_name VARCHAR(180) NOT NULL, name VARCHAR(180) NOT NULL, postal_code VARCHAR(10) NOT NULL, department VARCHAR(100) NOT NULL, region VARCHAR(100) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, INDEX city_search_name (search_name), INDEX city_postal_code (postal_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, message LONGTEXT NOT NULL, newsletter TINYINT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, INDEX contact_message_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, fixed_price INT DEFAULT NULL, position INT NOT NULL, active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guest_range (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, price INT NOT NULL, position INT NOT NULL, active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(150) DEFAULT NULL, city VARCHAR(150) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, source VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_401562C3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, kind VARCHAR(30) NOT NULL, content LONGTEXT NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, published TINYINT NOT NULL, in_menu TINYINT NOT NULL, in_footer TINYINT NOT NULL, position INT NOT NULL, menu_color VARCHAR(7) DEFAULT NULL, legacy_id INT DEFAULT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_140AB620989D9B62 (slug), UNIQUE INDEX UNIQ_140AB620184998FC (legacy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE party (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(80) NOT NULL, access_code VARCHAR(50) NOT NULL, event_date DATE DEFAULT NULL, published TINYINT NOT NULL, legacy_id INT DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_89954EE0989D9B62 (slug), UNIQUE INDEX UNIQ_89954EE081CC569E (access_code), UNIQUE INDEX UNIQ_89954EE0184998FC (legacy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE party_photo (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, filename VARCHAR(150) NOT NULL, original_name VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, party_id INT NOT NULL, UNIQUE INDEX party_photo_filename (party_id, filename), INDEX IDX_B72A4B7213C1059 (party_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo_access_log (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, city VARCHAR(180) NOT NULL, postal_code VARCHAR(10) DEFAULT NULL, newsletter TINYINT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, party_id INT NOT NULL, INDEX IDX_A31BDB18213C1059 (party_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote_request (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) DEFAULT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) NOT NULL, postal_address LONGTEXT DEFAULT NULL, event_type VARCHAR(100) DEFAULT NULL, guest_range VARCHAR(100) NOT NULL, city VARCHAR(180) NOT NULL, postal_code VARCHAR(10) DEFAULT NULL, event_date DATE NOT NULL, details LONGTEXT DEFAULT NULL, newsletter TINYINT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, distance_km INT NOT NULL, service_price INT NOT NULL, travel_price INT NOT NULL, total_price INT NOT NULL, pricing_rule VARCHAR(255) NOT NULL, read_at DATETIME DEFAULT NULL, INDEX quote_request_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, site_name VARCHAR(100) NOT NULL, site_title VARCHAR(255) NOT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, public_email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, contact_recipients JSON NOT NULL, quote_recipients JSON NOT NULL, google_analytics_id VARCHAR(30) DEFAULT NULL, departure_latitude DOUBLE PRECISION NOT NULL, departure_longitude DOUBLE PRECISION NOT NULL, road_factor DOUBLE PRECISION NOT NULL, short_trip_max_km INT NOT NULL, short_trip_fee INT NOT NULL, long_trip_base_fee INT NOT NULL, long_trip_price_per_km DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE special_date (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, day SMALLINT NOT NULL, month SMALLINT NOT NULL, price INT NOT NULL, UNIQUE INDEX special_date_day_month (day, month), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(120) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE party_photo ADD CONSTRAINT FK_B72A4B7213C1059 FOREIGN KEY (party_id) REFERENCES party (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo_access_log ADD CONSTRAINT FK_A31BDB18213C1059 FOREIGN KEY (party_id) REFERENCES party (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE party_photo DROP FOREIGN KEY FK_B72A4B7213C1059');
        $this->addSql('ALTER TABLE photo_access_log DROP FOREIGN KEY FK_A31BDB18213C1059');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE event_type');
        $this->addSql('DROP TABLE guest_range');
        $this->addSql('DROP TABLE newsletter_subscriber');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE party');
        $this->addSql('DROP TABLE party_photo');
        $this->addSql('DROP TABLE photo_access_log');
        $this->addSql('DROP TABLE quote_request');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE special_date');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
