<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Form\PageType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/pages', name: 'admin_page_')]
class PageAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PageRepository $pageRepository): Response
    {
        $pages = $pageRepository->findBy([], ['position' => 'ASC', 'title' => 'ASC']);

        return $this->render('admin/page/index.html.twig', [
            'pages' => $pages,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = (new Page())
            ->setPosition(100)
            ->setShowInMenu(true)
            ->setIsPublished(true);

        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($page);
            $entityManager->flush();

            $this->addFlash('success', 'Страница успешно создана.');

            return $this->redirectToRoute('admin_page_index');
        }

        return $this->render('admin/page/new.html.twig', [
            'form' => $form,
            'page' => $page,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Page $page, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Страница обновлена.');

            return $this->redirectToRoute('admin_page_index');
        }

        return $this->render('admin/page/edit.html.twig', [
            'form' => $form,
            'page' => $page,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Page $page, Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->getPayload()->get('_token');
        if (!$this->isCsrfTokenValid('delete_page_'.$page->getId(), $token)) {
            $this->addFlash('danger', 'Некорректный CSRF токен.');

            return $this->redirectToRoute('admin_page_index');
        }

        if ($page->getSystemKey() !== null) {
            $this->addFlash('danger', 'Системные страницы нельзя удалять.');

            return $this->redirectToRoute('admin_page_index');
        }

        $entityManager->remove($page);
        $entityManager->flush();

        $this->addFlash('success', 'Страница удалена.');

        return $this->redirectToRoute('admin_page_index');
    }
}
