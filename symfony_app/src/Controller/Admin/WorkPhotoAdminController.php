<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\WorkPhoto;
use App\Form\WorkPhotoType;
use App\Repository\WorkPhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/work-photos', name: 'admin_work_photo_')]
class WorkPhotoAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(WorkPhotoRepository $workPhotoRepository): Response
    {
        $photos = $workPhotoRepository->findBy([], ['sortOrder' => 'ASC', 'createdAt' => 'DESC']);

        return $this->render('admin/work_photo/index.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $photo = (new WorkPhoto())
            ->setSortOrder(100)
            ->setIsPublished(true);

        $form = $this->createForm(WorkPhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($photo);
            $entityManager->flush();

            $this->addFlash('success', 'Фото добавлено.');

            return $this->redirectToRoute('admin_work_photo_index');
        }

        return $this->render('admin/work_photo/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(WorkPhoto $photo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WorkPhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Фото обновлено.');

            return $this->redirectToRoute('admin_work_photo_index');
        }

        return $this->render('admin/work_photo/edit.html.twig', [
            'form' => $form,
            'photo' => $photo,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(WorkPhoto $photo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->getPayload()->get('_token');
        if (!$this->isCsrfTokenValid('delete_work_photo_'.$photo->getId(), $token)) {
            $this->addFlash('danger', 'Некорректный CSRF токен.');

            return $this->redirectToRoute('admin_work_photo_index');
        }

        $entityManager->remove($photo);
        $entityManager->flush();

        $this->addFlash('success', 'Фото удалено.');

        return $this->redirectToRoute('admin_work_photo_index');
    }
}
