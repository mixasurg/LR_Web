<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FeedbackMessage;
use App\Entity\Page;
use App\Entity\Work;
use App\Form\FeedbackMessageType;
use App\Repository\PageRepository;
use App\Repository\WorkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PublicController extends AbstractController
{
    private const HOME_WORKS_CACHE_KEY = 'public.home.works.v1';
    private const GALLERY_WORKS_CACHE_KEY = 'public.gallery.works.v1';
    private const WORKS_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly WorkRepository $workRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        $page = $this->getSystemPageOr404('home');
        $worksCacheHit = true;
        $works = $this->cache->get(self::HOME_WORKS_CACHE_KEY, function (ItemInterface $item) use (&$worksCacheHit): array {
            $worksCacheHit = false;
            $item->expiresAfter(self::WORKS_CACHE_TTL_SECONDS);

            $works = $this->workRepository->findPublishedFeatured(6);
            if ($works === []) {
                $works = array_slice($this->workRepository->findPublishedOrdered(), 0, 6);
            }

            return array_map(fn (Work $work): array => $this->buildWorkCard($work, 110), $works);
        });

        $response = $this->render('public/home.html.twig', [
            'page' => $page,
            'works' => $works,
            'uiTheme' => 'blood-angels',
        ]);

        $response->headers->set('X-Home-Works-Cache', $worksCacheHit ? 'HIT' : 'MISS');

        return $response;
    }

    #[Route('/contacts', name: 'app_contacts', methods: ['GET'])]
    public function contacts(): Response
    {
        $page = $this->getSystemPageOr404('contacts');

        return $this->render('public/static_page.html.twig', [
            'page' => $page,
            'uiTheme' => $this->resolveThemeForPage($page),
        ]);
    }

    #[Route('/gallery', name: 'app_gallery', methods: ['GET'])]
    public function gallery(): Response
    {
        $page = $this->getSystemPageOr404('gallery');
        $worksCacheHit = true;
        $works = $this->cache->get(self::GALLERY_WORKS_CACHE_KEY, function (ItemInterface $item) use (&$worksCacheHit): array {
            $worksCacheHit = false;
            $item->expiresAfter(self::WORKS_CACHE_TTL_SECONDS);

            return array_map(
                fn (Work $work): array => $this->buildWorkCard($work, 120),
                $this->workRepository->findPublishedOrdered(),
            );
        });

        $response = $this->render('public/gallery.html.twig', [
            'page' => $page,
            'works' => $works,
            'uiTheme' => $this->resolveThemeForPage($page),
        ]);

        $response->headers->set('X-Gallery-Works-Cache', $worksCacheHit ? 'HIT' : 'MISS');

        return $response;
    }

    #[Route('/works/{slug}', name: 'app_work_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function workShow(string $slug): Response
    {
        $work = $this->workRepository->findPublishedBySlug($slug);
        if ($work === null) {
            throw $this->createNotFoundException('Работа не найдена.');
        }

        return $this->render('public/work_show.html.twig', [
            'work' => $work,
            'photos' => $work->getPublishedPhotos(),
            'uiTheme' => $work->getVisualTheme(),
        ]);
    }

    #[Route('/feedback', name: 'app_feedback', methods: ['GET', 'POST'])]
    public function feedback(Request $request): Response
    {
        $page = $this->getSystemPageOr404('feedback');

        $feedback = new FeedbackMessage();
        $form = $this->createForm(FeedbackMessageType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($feedback);
            $this->entityManager->flush();

            $this->addFlash('success', 'Спасибо! Сообщение успешно отправлено.');

            return $this->redirectToRoute('app_feedback');
        }

        return $this->render('public/feedback.html.twig', [
            'page' => $page,
            'form' => $form,
            'uiTheme' => $this->resolveThemeForPage($page),
        ]);
    }

    #[Route('/pages/{slug}', name: 'app_page_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        $page = $this->pageRepository->findPublishedBySlug($slug);
        if ($page === null) {
            throw $this->createNotFoundException('Страница не найдена.');
        }

        return $this->render('public/static_page.html.twig', [
            'page' => $page,
            'uiTheme' => $this->resolveThemeForPage($page),
        ]);
    }

    private function getSystemPageOr404(string $key): Page
    {
        $page = $this->pageRepository->findPublishedSystemPage($key);
        if ($page === null) {
            throw $this->createNotFoundException(sprintf('Системная страница "%s" не найдена.', $key));
        }

        return $page;
    }

    private function resolveThemeForPage(Page $page): string
    {
        $scope = mb_strtolower(trim(sprintf('%s %s %s', $page->getSlug(), $page->getSystemKey(), $page->getTitle())));

        if (str_contains($scope, 'heresy')) {
            return 'sons-of-horus';
        }

        if (str_contains($scope, 'aos')
            || str_contains($scope, 'sigmar')
            || str_contains($scope, 'stormcast')) {
            return 'stormcast';
        }

        return 'blood-angels';
    }

    /**
     * @return array{
     *     title: string,
     *     slug: string,
     *     shortDescription: string,
     *     visualTheme: string,
     *     photoCount: int,
     *     coverImagePath: string|null
     * }
     */
    private function buildWorkCard(Work $work, int $descriptionLimit): array
    {
        $description = trim(strip_tags((string) $work->getDescription()));

        return [
            'title' => (string) $work->getTitle(),
            'slug' => (string) $work->getSlug(),
            'shortDescription' => $this->truncateText($description, $descriptionLimit),
            'visualTheme' => $work->getVisualTheme(),
            'photoCount' => $work->getPhotoCount(),
            'coverImagePath' => $work->getCoverPhoto()?->getImagePath(),
        ];
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $maxLength - 3))).'...';
    }
}
