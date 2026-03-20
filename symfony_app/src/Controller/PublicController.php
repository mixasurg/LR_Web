<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FeedbackMessage;
use App\Entity\Page;
use App\Form\FeedbackMessageType;
use App\Repository\PageRepository;
use App\Repository\WorkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly WorkRepository $workRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        $page = $this->getSystemPageOr404('home');
        $works = $this->workRepository->findPublishedFeatured(6);
        if ($works === []) {
            $works = array_slice($this->workRepository->findPublishedOrdered(), 0, 6);
        }

        return $this->render('public/home.html.twig', [
            'page' => $page,
            'works' => $works,
            'uiTheme' => 'blood-angels',
        ]);
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

        return $this->render('public/gallery.html.twig', [
            'page' => $page,
            'works' => $this->workRepository->findPublishedOrdered(),
            'uiTheme' => $this->resolveThemeForPage($page),
        ]);
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
}
