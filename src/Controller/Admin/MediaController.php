<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use App\Security\Voter\MediaVoter;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media')]
class MediaController extends AbstractController
{
    public const PER_PAGE = 25;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly MediaUploader $mediaUploader,
    ) {
    }

    #[Route('', name: 'admin_media_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        /** @var User|null $owner */
        $owner = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();

        $medias = $this->mediaRepository->findPageForAdmin($owner, $page, self::PER_PAGE);
        $total = $this->mediaRepository->count(null === $owner ? [] : ['user' => $owner]);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PER_PAGE)),
        ]);
    }

    #[Route('/add', name: 'admin_media_add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $media = new Media();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $isAdmin]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isAdmin) {
                /** @var User $user */
                $user = $this->getUser();
                $media->setUser($user);
            }
            $this->mediaUploader->upload($media);
            $this->entityManager->persist($media);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le média a bien été ajouté.');

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form]);
    }

    #[Route('/delete/{id}', name: 'admin_media_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(MediaVoter::MANAGE, subject: 'media')]
    #[IsCsrfTokenValid(new Expression('"delete-media-" ~ args["media"].getId()'), tokenKey: '_token')]
    public function delete(Media $media): Response
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();
        $this->mediaUploader->remove($media);
        $this->addFlash('success', 'Le média a bien été supprimé.');

        return $this->redirectToRoute('admin_media_index');
    }
}
