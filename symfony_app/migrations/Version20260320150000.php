<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replaces uploads segment in work_photo.image_path with mixas-works.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE work_photo
             SET image_path = REPLACE(REPLACE(image_path, '/uploads/', '/mixas-works/'), 'uploads/', 'mixas-works/')
             WHERE image_path LIKE '%/uploads/%'
                OR image_path LIKE 'uploads/%'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE work_photo
             SET image_path = REPLACE(REPLACE(image_path, '/mixas-works/', '/uploads/'), 'mixas-works/', 'uploads/')
             WHERE image_path LIKE '%/mixas-works/%'
                OR image_path LIKE 'mixas-works/%'"
        );
    }
}
