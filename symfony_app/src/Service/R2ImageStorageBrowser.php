<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

final class R2ImageStorageBrowser
{
    /**
     * @var list<string>
     */
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    public function __construct(
        private readonly string $r2Endpoint,
        private readonly string $r2Bucket,
        private readonly string $r2AccessKeyId,
        private readonly string $r2SecretAccessKey,
        private readonly string $r2Region,
        private readonly string $r2ImagesPrefix,
        private readonly int $r2ListLimit,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->r2Endpoint !== ''
            && $this->r2Bucket !== ''
            && $this->r2AccessKeyId !== ''
            && $this->r2SecretAccessKey !== '';
    }

    /**
     * @return list<string>
     */
    public function listImagePaths(): array
    {
        if (!$this->isConfigured() || $this->r2ListLimit <= 0) {
            return [];
        }

        $paths = [];
        $prefix = $this->normalizePrefix($this->r2ImagesPrefix);
        $continuationToken = null;
        $remaining = $this->r2ListLimit;

        try {
            $client = $this->createClient();

            do {
                $params = [
                    'Bucket' => $this->r2Bucket,
                    'MaxKeys' => min(1000, $remaining),
                ];

                if ($prefix !== '') {
                    $params['Prefix'] = $prefix;
                }

                if ($continuationToken !== null) {
                    $params['ContinuationToken'] = $continuationToken;
                }

                $result = $client->listObjectsV2($params);
                $contents = $result['Contents'] ?? [];

                foreach ($contents as $item) {
                    $key = trim((string) ($item['Key'] ?? ''));
                    if ($key === '' || !$this->isImagePath($key)) {
                        continue;
                    }

                    $paths[] = ltrim($key, '/');
                    --$remaining;

                    if ($remaining <= 0) {
                        break;
                    }
                }

                if (!(bool) ($result['IsTruncated'] ?? false) || $remaining <= 0) {
                    break;
                }

                $nextToken = trim((string) ($result['NextContinuationToken'] ?? ''));
                $continuationToken = $nextToken !== '' ? $nextToken : null;
            } while ($continuationToken !== null);
        } catch (\Throwable $exception) {
            $this->logger->error('Не удалось получить список изображений из R2.', [
                'error' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Не удалось загрузить список фото из R2. Проверьте настройки доступа.');
        }

        sort($paths, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_unique($paths));
    }

    private function createClient(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $this->r2Region !== '' ? $this->r2Region : 'auto',
            'endpoint' => rtrim($this->r2Endpoint, '/'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $this->r2AccessKeyId,
                'secret' => $this->r2SecretAccessKey,
            ],
            'http' => [
                'timeout' => 6,
            ],
        ]);
    }

    private function normalizePrefix(string $prefix): string
    {
        $normalizedPrefix = ltrim(trim($prefix), '/');
        if ($normalizedPrefix === '') {
            return '';
        }

        return rtrim($normalizedPrefix, '/').'/';
    }

    private function isImagePath(string $path): bool
    {
        if (str_ends_with($path, '/')) {
            return false;
        }

        $extension = mb_strtolower((string) pathinfo($path, \PATHINFO_EXTENSION));

        return in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true);
    }
}
