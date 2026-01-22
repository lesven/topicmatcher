<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122073510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts ADD title VARCHAR(255) NOT NULL, ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA71F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('CREATE INDEX IDX_885DBAFA71F7E88B ON posts (event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA71F7E88B');
        $this->addSql('DROP INDEX IDX_885DBAFA71F7E88B ON posts');
        $this->addSql('ALTER TABLE posts DROP title, DROP event_id');
    }
}
