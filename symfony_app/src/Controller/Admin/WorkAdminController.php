<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Work;
use App\Form\WorkType;
use App\Repository\WorkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/works', name: 'admin_work_')]
class WorkAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(WorkRepository $workRepository): Response
    {
        $works = $workRepository->findBy([], ['sortOrder' => 'ASC', 'title' => 'ASC']);

        return $this->render('admin/work/index.html.twig', [
            'works' => $works,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $work = (new Work())
            ->setSortOrder(100)
            ->setIsPublished(true);

        $form = $this->createForm(WorkType::class, $work);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($work);
            $entityManager->flush();

            $this->addFlash('success', 'Работа создана.');

            return $this->redirectToRoute('admin_work_index');
        }

        return $this->render('admin/work/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Work $work, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WorkType::class, $work);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Работа обновлена.');

            return $this->redirectToRoute('admin_work_index');
        }

        return $this->render('admin/work/edit.html.twig', [
            'form' => $form,
            'work' => $work,
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
}
