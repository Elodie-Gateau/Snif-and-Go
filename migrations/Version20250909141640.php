<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909141640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_812C397D2BC7B602 ON dog (identity_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14B784185E237E06 ON photo (name)');
        $this->addSql('ALTER TABLE walk ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_812C397D2BC7B602 ON dog');
        $this->addSql('DROP INDEX UNIQ_14B784185E237E06 ON photo');
        $this->addSql('ALTER TABLE walk DROP status');
    }
}
