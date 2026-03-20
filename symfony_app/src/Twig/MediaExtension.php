<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\MediaUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    public function __construct(
        private readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_url', $this->mediaUrl(...)),
        ];
    }

    public function mediaUrl(?string $path): string
    {
        return $this->mediaUrlGenerator->generate($path);
    }
}
