<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122110439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD is_template TINYINT DEFAULT 0 NOT NULL, ADD template_source_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A5C6F6AD7 FOREIGN KEY (template_source_id) REFERENCES events (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5387574A5C6F6AD7 ON events (template_source_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A5C6F6AD7');
        $this->addSql('DROP INDEX IDX_5387574A5C6F6AD7 ON events');
        $this->addSql('ALTER TABLE events DROP is_template, DROP template_source_id');
    }
}
