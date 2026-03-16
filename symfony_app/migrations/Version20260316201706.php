<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316201706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE work (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, description CLOB NOT NULL, is_published BOOLEAN NOT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_work_slug ON work (slug)');
        $this->addSql('CREATE TABLE work_photo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_path VARCHAR(255) NOT NULL, caption VARCHAR(180) DEFAULT NULL, is_published BOOLEAN NOT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL, work_id INTEGER NOT NULL, CONSTRAINT FK_3C4CFF37BB3453DB FOREIGN KEY (work_id) REFERENCES work (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3C4CFF37BB3453DB ON work_photo (work_id)');
        $this->addSql('DROP TABLE gallery_image');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gallery_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL COLLATE "BINARY", image_path VARCHAR(255) NOT NULL COLLATE "BINARY", description CLOB DEFAULT NULL COLLATE "BINARY", is_published BOOLEAN NOT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('DROP TABLE work');
        $this->addSql('DROP TABLE work_photo');
    }
}
