<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prefixes existing work_photo.image_path values with MEDIA_BASE_URL for Cloudflare R2 migration.';
    }

    public function up(Schema $schema): void
    {
        $baseUrl = $this->requireMediaBaseUrl();
        $platform = $this->detectPlatform();

        if ($platform === 'sqlite') {
            $this->addSql(
                "UPDATE work_photo
                 SET image_path = :baseUrl || '/' || ltrim(image_path, '/')
                 WHERE image_path <> ''
                   AND image_path NOT LIKE 'http://%'
                   AND image_path NOT LIKE 'https://%'",
                ['baseUrl' => $baseUrl]
            );

            return;
        }

        if ($platform === 'mysql') {
            $this->addSql(
                "UPDATE work_photo
                 SET image_path = CONCAT(:baseUrl, '/', TRIM(LEADING '/' FROM image_path))
                 WHERE image_path <> ''
                   AND image_path NOT LIKE 'http://%'
                   AND image_path NOT LIKE 'https://%'",
                ['baseUrl' => $baseUrl]
            );

            return;
        }

        if ($platform === 'postgresql') {
            $this->addSql(
                "UPDATE work_photo
                 SET image_path = :baseUrl || '/' || ltrim(image_path, '/')
                 WHERE image_path <> ''
                   AND image_path NOT LIKE 'http://%'
                   AND image_path NOT LIKE 'https://%'",
                ['baseUrl' => $baseUrl]
            );

            return;
        }

        $this->abortIf(true, sprintf('Unsupported database platform "%s".', $platform));
    }

    public function down(Schema $schema): void
    {
        $baseUrl = $this->requireMediaBaseUrl();
        $prefix = rtrim($baseUrl, '/').'/';
        $prefixLike = $prefix.'%';
        $platform = $this->detectPlatform();

        if ($platform === 'sqlite') {
            $this->addSql(
                'UPDATE work_photo SET image_path = substr(image_path, length(:prefix) + 1) WHERE image_path LIKE :prefixLike',
                [
                    'prefix' => $prefix,
                    'prefixLike' => $prefixLike,
                ]
            );

            return;
        }

        if ($platform === 'mysql') {
            $this->addSql(
                'UPDATE work_photo SET image_path = SUBSTRING(image_path, CHAR_LENGTH(:prefix) + 1) WHERE image_path LIKE :prefixLike',
                [
                    'prefix' => $prefix,
                    'prefixLike' => $prefixLike,
                ]
            );

            return;
        }

        if ($platform === 'postgresql') {
            $this->addSql(
                'UPDATE work_photo SET image_path = substring(image_path from char_length(:prefix) + 1) WHERE image_path LIKE :prefixLike',
                [
                    'prefix' => $prefix,
                    'prefixLike' => $prefixLike,
                ]
            );

            return;
        }

        $this->abortIf(true, sprintf('Unsupported database platform "%s".', $platform));
    }

    private function requireMediaBaseUrl(): string
    {
        $mediaBaseUrl = rtrim(trim($this->readEnvValue('MEDIA_BASE_URL')), '/');
        if ($mediaBaseUrl === '') {
            throw new \RuntimeException('MEDIA_BASE_URL is empty. Set MEDIA_BASE_URL before running this migration.');
        }

        if (!preg_match('#^https?://#i', $mediaBaseUrl)) {
            throw new \RuntimeException('MEDIA_BASE_URL must start with http:// or https://.');
        }

        return $mediaBaseUrl;
    }

    private function readEnvValue(string $name): string
    {
        $value = getenv($name);
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        $serverValue = $_SERVER[$name] ?? null;
        if (is_string($serverValue) && trim($serverValue) !== '') {
            return $serverValue;
        }

        $envValue = $_ENV[$name] ?? null;
        if (is_string($envValue) && trim($envValue) !== '') {
            return $envValue;
        }

        return '';
    }

    private function detectPlatform(): string
    {
        $platform = $this->connection->getDatabasePlatform();

        return match (true) {
            $platform instanceof SQLitePlatform => 'sqlite',
            $platform instanceof MySQLPlatform => 'mysql',
            $platform instanceof PostgreSQLPlatform => 'postgresql',
            default => $platform::class,
        };
    }
}
