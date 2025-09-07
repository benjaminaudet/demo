<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907174431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE symfony_demo_access_token ADD CONSTRAINT FK_CEFBA314A76ED395 FOREIGN KEY (user_id) REFERENCES symfony_demo_user (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CEFBA314A76ED395 ON symfony_demo_access_token (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE symfony_demo_access_token DROP CONSTRAINT FK_CEFBA314A76ED395');
        $this->addSql('DROP INDEX UNIQ_CEFBA314A76ED395');
    }
}
