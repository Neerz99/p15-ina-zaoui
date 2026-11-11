<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des invités, réservée à Ina (ROLE_ADMIN).
 */
#[Route('/admin/guest')]
#[IsGranted('ROLE_ADMIN')]
class GuestController extends AbstractController
{
    /**
     * Seuls les invités sont résolus (jamais Ina) : sinon 404, avant même la vérification CSRF.
     */
    private const GUEST_ONLY = 'repository.findOneBy({"id": id, "admin": false})';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'admin_guest_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $this->userRepository->findGuestsWithMediaCount(),
        ]);
    }

    #[Route('/add', name: 'admin_guest_add', methods: ['GET', 'POST'])]
    public function add(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $guest->setAdmin(false);
            $guest->setPassword($passwordHasher->hashPassword($guest, (string) $form->get('plainPassword')->getData()));
            $this->entityManager->persist($guest);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('L\'invité « %s » a bien été ajouté.', $guest->getName()));

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/toggle-block', name: 'admin_guest_toggle_block', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"toggle-guest-" ~ args["guest"].getId()'), tokenKey: '_token')]
    public function toggleBlock(#[MapEntity(expr: self::GUEST_ONLY)] User $guest): Response
    {
        $guest->setBlocked(!$guest->isBlocked());
        $this->entityManager->flush();

        $this->addFlash('success', $guest->isBlocked()
            ? sprintf('L\'accès de « %s » a été révoqué : ses photos ne sont plus affichées.', $guest->getName())
            : sprintf('L\'accès de « %s » a été rétabli.', $guest->getName()));

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/{id}/delete', name: 'admin_guest_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete-guest-" ~ args["guest"].getId()'), tokenKey: '_token')]
    public function delete(#[MapEntity(expr: self::GUEST_ONLY)] User $guest, MediaUploader $mediaUploader): Response
    {
        // Suppression en cascade : les médias sont supprimés en base (orphanRemoval / ON DELETE CASCADE)
        // et les fichiers correspondants sont retirés du disque.
        $medias = $guest->getMedias()->toArray();
        $name = $guest->getName();

        $this->entityManager->remove($guest);
        $this->entityManager->flush();

        foreach ($medias as $media) {
            $mediaUploader->remove($media);
        }

        $this->addFlash('success', sprintf('L\'invité « %s » et ses %d média(s) ont été supprimés.', $name, \count($medias)));

        return $this->redirectToRoute('admin_guest_index');
    }
}
