<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Asset\Packages;

final class MediaUrlGenerator
{
    public function __construct(
        private readonly Packages $packages,
        private readonly string $mediaBaseUrl,
    ) {
    }

    public function generate(?string $path): string
    {
        $normalizedPath = ltrim(trim((string) $path), '/');
        if ($normalizedPath === '') {
            return '';
        }

        if ($this->isAbsoluteUrl($normalizedPath)) {
            return $normalizedPath;
        }

        $baseUrl = trim($this->mediaBaseUrl);
        if ($baseUrl === '') {
            return $this->packages->getUrl($normalizedPath);
        }

        return rtrim($baseUrl, '/').'/'.$normalizedPath;
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return preg_match('#^(?:https?:)?//#i', $path) === 1 || str_starts_with($path, 'data:');
    }
}
