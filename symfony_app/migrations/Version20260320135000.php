<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes legacy segment from work_photo.image_path values (uploads/legacy/* -> uploads/*).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE work_photo
             SET image_path = REPLACE(REPLACE(image_path, '/uploads/legacy/', '/uploads/'), 'uploads/legacy/', 'uploads/')
             WHERE image_path LIKE '%uploads/legacy/%'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE work_photo
             SET image_path = REPLACE(REPLACE(image_path, '/uploads/', '/uploads/legacy/'), 'uploads/', 'uploads/legacy/')
             WHERE image_path LIKE '%uploads/%'
               AND image_path NOT LIKE '%uploads/legacy/%'"
        );
    }
}
