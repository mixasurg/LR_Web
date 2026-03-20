<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds featured flag for works to support up to 6 selected home page items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work ADD COLUMN is_featured BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing is_featured is not supported by this migration.');
    }
}
