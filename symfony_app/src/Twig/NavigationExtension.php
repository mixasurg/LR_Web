<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Page;
use App\Repository\PageRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    private const NAVIGATION_CACHE_KEY = 'public.navigation.pages.v1';
    private const NAVIGATION_CACHE_TTL_SECONDS = 600;

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigation_pages', $this->getNavigationPages(...)),
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function getNavigationPages(): array
    {
        return $this->cache->get(self::NAVIGATION_CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::NAVIGATION_CACHE_TTL_SECONDS);

            $result = [];
            foreach ($this->pageRepository->findVisibleForMenu() as $page) {
                $result[] = [
                    'label' => $page->getMenuLabel(),
                    'url' => $this->resolveUrl($page),
                ];
            }

            return $result;
        });
    }

    private function resolveUrl(Page $page): string
    {
        return match ($page->getSystemKey()) {
            'home' => $this->urlGenerator->generate('app_home'),
            'contacts' => $this->urlGenerator->generate('app_contacts'),
            'gallery' => $this->urlGenerator->generate('app_gallery'),
            'feedback' => $this->urlGenerator->generate('app_feedback'),
            default => $this->urlGenerator->generate('app_page_show', ['slug' => $page->getSlug()]),
        };
    }
}
