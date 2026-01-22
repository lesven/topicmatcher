<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove event_id column and constraint from categories table';
    }

    public function up(Schema $schema): void
    {
        // Remove foreign key constraint first
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF3466871F7E88B');
        // Remove index
        $this->addSql('DROP INDEX IDX_3AF3466871F7E88B ON categories');
        // Remove unique constraint
        $this->addSql('DROP INDEX unique_category_per_event ON categories'); 
        // Remove column
        $this->addSql('ALTER TABLE categories DROP COLUMN event_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF3466871F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('CREATE INDEX IDX_3AF3466871F7E88B ON categories (event_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_category_per_event ON categories (event_id, name)');
    }
}