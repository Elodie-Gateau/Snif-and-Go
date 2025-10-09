<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009075539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dog (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, dog_breed_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, birth_date DATETIME NOT NULL, sex VARCHAR(255) NOT NULL, identity_number VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, cdn_link VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_812C397D2BC7B602 (identity_number), INDEX IDX_812C397DA76ED395 (user_id), INDEX IDX_812C397DC0EB1E2E (dog_breed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dog_breed (id INT AUTO_INCREMENT NOT NULL, name_fr VARCHAR(255) NOT NULL, name_en VARCHAR(255) NOT NULL, size VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, trail_id INT DEFAULT NULL, walk_id INT DEFAULT NULL, date DATETIME NOT NULL, name VARCHAR(255) NOT NULL, cdn_link VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_14B784185E237E06 (name), INDEX IDX_14B78418A76ED395 (user_id), INDEX IDX_14B7841889B51C5B (trail_id), INDEX IDX_14B784185EEE1B48 (walk_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trail (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, duration DOUBLE PRECISION DEFAULT NULL, difficulty VARCHAR(255) DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, water_point VARCHAR(255) DEFAULT NULL, start_address VARCHAR(255) DEFAULT NULL, start_code VARCHAR(10) DEFAULT NULL, start_city VARCHAR(255) DEFAULT NULL, end_address VARCHAR(255) DEFAULT NULL, end_code VARCHAR(10) DEFAULT NULL, end_city VARCHAR(255) DEFAULT NULL, name_search VARCHAR(255) DEFAULT NULL, start_city_search VARCHAR(255) DEFAULT NULL, end_city_search VARCHAR(255) DEFAULT NULL, gpx_file VARCHAR(255) DEFAULT NULL, input_mode VARCHAR(255) DEFAULT NULL, start_lat DOUBLE PRECISION DEFAULT NULL, start_lon DOUBLE PRECISION DEFAULT NULL, end_lat DOUBLE PRECISION DEFAULT NULL, end_lon DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_B268858FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE walk (id INT AUTO_INCREMENT NOT NULL, trail_id INT NOT NULL, user_id INT NOT NULL, date DATETIME NOT NULL, max_dogs INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_8D917A5589B51C5B (trail_id), INDEX IDX_8D917A55A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE walk_registration (id INT AUTO_INCREMENT NOT NULL, dog_id INT NOT NULL, walk_id INT NOT NULL, date_registration DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_E94984DC634DFEB (dog_id), INDEX IDX_E94984DC5EEE1B48 (walk_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dog ADD CONSTRAINT FK_812C397DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE dog ADD CONSTRAINT FK_812C397DC0EB1E2E FOREIGN KEY (dog_breed_id) REFERENCES dog_breed (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841889B51C5B FOREIGN KEY (trail_id) REFERENCES trail (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784185EEE1B48 FOREIGN KEY (walk_id) REFERENCES walk (id)');
        $this->addSql('ALTER TABLE trail ADD CONSTRAINT FK_B268858FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE walk ADD CONSTRAINT FK_8D917A5589B51C5B FOREIGN KEY (trail_id) REFERENCES trail (id)');
        $this->addSql('ALTER TABLE walk ADD CONSTRAINT FK_8D917A55A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE walk_registration ADD CONSTRAINT FK_E94984DC634DFEB FOREIGN KEY (dog_id) REFERENCES dog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE walk_registration ADD CONSTRAINT FK_E94984DC5EEE1B48 FOREIGN KEY (walk_id) REFERENCES walk (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dog DROP FOREIGN KEY FK_812C397DA76ED395');
        $this->addSql('ALTER TABLE dog DROP FOREIGN KEY FK_812C397DC0EB1E2E');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418A76ED395');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B7841889B51C5B');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784185EEE1B48');
        $this->addSql('ALTER TABLE trail DROP FOREIGN KEY FK_B268858FA76ED395');
        $this->addSql('ALTER TABLE walk DROP FOREIGN KEY FK_8D917A5589B51C5B');
        $this->addSql('ALTER TABLE walk DROP FOREIGN KEY FK_8D917A55A76ED395');
        $this->addSql('ALTER TABLE walk_registration DROP FOREIGN KEY FK_E94984DC634DFEB');
        $this->addSql('ALTER TABLE walk_registration DROP FOREIGN KEY FK_E94984DC5EEE1B48');
        $this->addSql('DROP TABLE dog');
        $this->addSql('DROP TABLE dog_breed');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE trail');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE walk');
        $this->addSql('DROP TABLE walk_registration');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
