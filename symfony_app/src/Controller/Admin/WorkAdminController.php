<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Work;
use App\Entity\WorkPhoto;
use App\Form\WorkType;
use App\Repository\WorkRepository;
use App\Service\R2ImageStorageBrowser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/works', name: 'admin_work_')]
class WorkAdminController extends AbstractController
{
    public function __construct(
        private readonly R2ImageStorageBrowser $r2ImageStorageBrowser,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(WorkRepository $workRepository): Response
    {
        $works = $workRepository->findBy([], ['sortOrder' => 'ASC', 'title' => 'ASC']);
        $featuredCount = $workRepository->countFeaturedWorks();

        return $this->render('admin/work/index.html.twig', [
            'works' => $works,
            'featuredCount' => $featuredCount,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        WorkRepository $workRepository,
    ): Response
    {
        $work = (new Work())
            ->setSortOrder(100)
            ->setIsPublished(true);

        $storagePicker = $this->loadStorageImagePicker();

        $form = $this->createForm(WorkType::class, $work, [
            'storage_image_choices' => $storagePicker['choices'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->validateFeaturedState($work, $form, $workRepository, true)) {
                return $this->render('admin/work/new.html.twig', [
                    'form' => $form,
                    'storageLoadError' => $storagePicker['error'],
                ]);
            }

            $addedPhotos = $this->attachSelectedPhotosToWork($work, $form);

            $entityManager->persist($work);
            $entityManager->flush();

            $message = 'Работа создана.';
            if ($addedPhotos > 0) {
                $message .= sprintf(' Добавлено фото из R2: %d.', $addedPhotos);
            }
            $this->addFlash('success', $message);

            return $this->redirectToRoute('admin_work_index');
        }

        return $this->render('admin/work/new.html.twig', [
            'form' => $form,
            'storageLoadError' => $storagePicker['error'],
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Work $work,
        Request $request,
        EntityManagerInterface $entityManager,
        WorkRepository $workRepository,
    ): Response
    {
        $storagePicker = $this->loadStorageImagePicker();
        $selectedPhotoIdsToRemove = $request->isMethod('POST')
            ? $this->extractSelectedPhotoIds($request)
            : [];

        $form = $this->createForm(WorkType::class, $work, [
            'storage_image_choices' => $storagePicker['choices'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->validateFeaturedState($work, $form, $workRepository, false)) {
                return $this->render('admin/work/edit.html.twig', [
                    'form' => $form,
                    'work' => $work,
                    'storageLoadError' => $storagePicker['error'],
                    'currentPhotos' => $this->buildCurrentPhotosForEdit($work),
                    'selectedPhotoIdsToRemove' => $selectedPhotoIdsToRemove,
                ]);
            }

            $removedPhotos = $this->removeSelectedPhotosFromWork($work, $selectedPhotoIdsToRemove);
            $addedPhotos = $this->attachSelectedPhotosToWork($work, $form);

            $entityManager->flush();

            $message = 'Работа обновлена.';
            if ($removedPhotos > 0) {
                $message .= sprintf(' Удалено фото: %d.', $removedPhotos);
            }
            if ($addedPhotos > 0) {
                $message .= sprintf(' Добавлено фото из R2: %d.', $addedPhotos);
            }
            $this->addFlash('success', $message);

            return $this->redirectToRoute('admin_work_index');
        }

        return $this->render('admin/work/edit.html.twig', [
            'form' => $form,
            'work' => $work,
            'storageLoadError' => $storagePicker['error'],
            'currentPhotos' => $this->buildCurrentPhotosForEdit($work),
            'selectedPhotoIdsToRemove' => $selectedPhotoIdsToRemove,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Work $work, Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->getPayload()->get('_token');
        if (!$this->isCsrfTokenValid('delete_work_'.$work->getId(), $token)) {
            $this->addFlash('danger', 'Некорректный CSRF токен.');

            return $this->redirectToRoute('admin_work_index');
        }

        $entityManager->remove($work);
        $entityManager->flush();

        $this->addFlash('success', 'Работа удалена.');

        return $this->redirectToRoute('admin_work_index');
    }

    /**
     * @return array{choices: array<string, string>, error: ?string}
     */
    private function loadStorageImagePicker(): array
    {
        if (!$this->r2ImageStorageBrowser->isConfigured()) {
            return [
                'choices' => [],
                'error' => null,
            ];
        }

        try {
            $imagePaths = $this->r2ImageStorageBrowser->listImagePaths();
        } catch (\RuntimeException $exception) {
            return [
                'choices' => [],
                'error' => $exception->getMessage(),
            ];
        }

        $choices = [];
        foreach ($imagePaths as $path) {
            $choices[$path] = $path;
        }

        return [
            'choices' => $choices,
            'error' => null,
        ];
    }

    private function attachSelectedPhotosToWork(Work $work, FormInterface $form): int
    {
        $selectedPaths = $form->get('storageImagePaths')->getData();
        if (!is_iterable($selectedPaths)) {
            return 0;
        }

        $existingPaths = [];
        foreach ($work->getPhotos() as $photo) {
            $path = $photo->getImagePath();
            if ($path !== null && $path !== '') {
                $existingPaths[$path] = true;
            }
        }

        $nextSortOrder = $this->resolveNextPhotoSortOrder($work);
        $added = 0;

        foreach ($selectedPaths as $selectedPath) {
            $normalizedPath = ltrim(trim((string) $selectedPath), '/');
            if ($normalizedPath === '' || isset($existingPaths[$normalizedPath])) {
                continue;
            }

            $photo = (new WorkPhoto())
                ->setImagePath($normalizedPath)
                ->setSortOrder($nextSortOrder)
                ->setIsPublished(true);

            $work->addPhoto($photo);
            $existingPaths[$normalizedPath] = true;
            $nextSortOrder += 10;
            ++$added;
        }

        return $added;
    }

    private function resolveNextPhotoSortOrder(Work $work): int
    {
        $highestSortOrder = 90;
        foreach ($work->getPhotos() as $photo) {
            $highestSortOrder = max($highestSortOrder, $photo->getSortOrder());
        }

        return $highestSortOrder + 10;
    }

    /**
     * @return list<int>
     */
    private function extractSelectedPhotoIds(Request $request): array
    {
        $rawPhotoIds = $request->getPayload()->all('remove_photo_ids');
        if (!is_array($rawPhotoIds)) {
            return [];
        }

        $selectedPhotoIds = [];
        foreach ($rawPhotoIds as $rawPhotoId) {
            $photoId = (int) $rawPhotoId;
            if ($photoId > 0) {
                $selectedPhotoIds[$photoId] = true;
            }
        }

        return array_keys($selectedPhotoIds);
    }

    /**
     * @param list<int> $selectedPhotoIds
     */
    private function removeSelectedPhotosFromWork(Work $work, array $selectedPhotoIds): int
    {
        if ($selectedPhotoIds === []) {
            return 0;
        }

        $selectedMap = array_fill_keys($selectedPhotoIds, true);
        $removed = 0;

        foreach ($work->getPhotos()->toArray() as $photo) {
            $photoId = $photo->getId();
            if ($photoId !== null && isset($selectedMap[$photoId])) {
                $work->removePhoto($photo);
                ++$removed;
            }
        }

        return $removed;
    }

    /**
     * @return list<array{id: int, path: string, caption: ?string, isPublished: bool, sortOrder: int}>
     */
    private function buildCurrentPhotosForEdit(Work $work): array
    {
        $photos = $work->getPhotos()->toArray();
        usort($photos, static fn (WorkPhoto $a, WorkPhoto $b): int => [$a->getSortOrder(), $a->getId() ?? 0] <=> [$b->getSortOrder(), $b->getId() ?? 0]);

        $result = [];
        foreach ($photos as $photo) {
            $photoId = $photo->getId();
            $photoPath = $photo->getImagePath();

            if ($photoId === null || $photoPath === null || $photoPath === '') {
                continue;
            }

            $result[] = [
                'id' => $photoId,
                'path' => $photoPath,
                'caption' => $photo->getCaption(),
                'isPublished' => $photo->isPublished(),
                'sortOrder' => $photo->getSortOrder(),
            ];
        }

        return $result;
    }

    private function validateFeaturedState(
        Work $work,
        FormInterface $form,
        WorkRepository $workRepository,
        bool $isNew,
    ): bool {
        if (!$work->isFeatured()) {
            return true;
        }

        if (!$work->isPublished()) {
            $form->get('isFeatured')->addError(new FormError('Избранной может быть только опубликованная работа.'));

            return false;
        }

        $excludeWorkId = $isNew ? null : $work->getId();
        $featuredCountWithoutCurrent = $workRepository->countFeaturedWorks($excludeWorkId);

        if ($featuredCountWithoutCurrent >= 6) {
            $form->get('isFeatured')->addError(new FormError('Можно выбрать не более 6 избранных работ.'));

            return false;
        }

        return true;
    }
}
