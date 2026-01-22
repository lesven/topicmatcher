<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122075000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add eventDate and location columns to events table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD event_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD location VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP COLUMN event_date');
        $this->addSql('ALTER TABLE events DROP COLUMN location');
    }
}