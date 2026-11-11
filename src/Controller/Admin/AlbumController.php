<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/album')]
#[IsGranted('ROLE_ADMIN')]
class AlbumController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    #[Route('', name: 'admin_album_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/album/index.html.twig', [
            'albums' => $this->albumRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/add', name: 'admin_album_add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($album);
            $this->entityManager->flush();
            $this->addFlash('success', "L'album a bien été créé.");

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form]);
    }

    #[Route('/update/{id}', name: 'admin_album_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(Request $request, Album $album): Response
    {
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', "L'album a bien été modifié.");

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form]);
    }

    #[Route('/delete/{id}', name: 'admin_album_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-album-" ~ args["album"].getId()'), tokenKey: '_token')]
    public function delete(Album $album): Response
    {
        // Les médias de l'album sont conservés (clé étrangère ON DELETE SET NULL).
        $this->entityManager->remove($album);
        $this->entityManager->flush();
        $this->addFlash('success', "L'album a bien été supprimé.");

        return $this->redirectToRoute('admin_album_index');
    }
}
