<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resets work.id numbering for SQLite and remaps work_photo.work_id accordingly.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
            return;
        }

        $this->connection->executeStatement('PRAGMA foreign_keys = OFF');

        $this->connection->executeStatement(
            'CREATE TABLE work_reindexed (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                title VARCHAR(180) NOT NULL,
                slug VARCHAR(180) NOT NULL,
                description CLOB NOT NULL,
                is_published BOOLEAN NOT NULL,
                sort_order INTEGER NOT NULL,
                created_at DATETIME NOT NULL
            )'
        );

        $this->connection->executeStatement(
            'INSERT INTO work_reindexed (title, slug, description, is_published, sort_order, created_at)
             SELECT title, slug, description, is_published, sort_order, created_at
             FROM work
             ORDER BY sort_order ASC, id ASC'
        );

        $this->connection->executeStatement(
            'CREATE TEMP TABLE work_id_map (
                old_id INTEGER PRIMARY KEY NOT NULL,
                new_id INTEGER NOT NULL
            )'
        );

        $this->connection->executeStatement(
            'INSERT INTO work_id_map (old_id, new_id)
             SELECT old_work.id, new_work.id
             FROM work old_work
             INNER JOIN work_reindexed new_work ON new_work.slug = old_work.slug'
        );

        $this->connection->executeStatement(
            'UPDATE work_photo
             SET work_id = (
                SELECT new_id
                FROM work_id_map
                WHERE old_id = work_photo.work_id
             )'
        );

        $this->connection->executeStatement('DROP TABLE work');
        $this->connection->executeStatement('ALTER TABLE work_reindexed RENAME TO work');
        $this->connection->executeStatement('CREATE UNIQUE INDEX uniq_work_slug ON work (slug)');
        $this->connection->executeStatement('DROP TABLE work_id_map');
        $this->connection->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Reindexed IDs cannot be restored safely.');
    }
}
