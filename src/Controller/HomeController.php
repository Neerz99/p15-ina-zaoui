<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $userRepository): Response
    {
        $guests = $userRepository->findBy(['admin' => false, 'blocked' => false]);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route('/guest/{id}', name: 'guest', requirements: ['id' => '\d+'])]
    public function guest(int $id, UserRepository $userRepository): Response
    {
        $guest = $userRepository->findActiveGuest($id);

        if (null === $guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio', requirements: ['id' => '\d+'])]
    public function portfolio(
        AlbumRepository $albumRepository,
        MediaRepository $mediaRepository,
        UserRepository $userRepository,
        ?int $id = null,
    ): Response {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;

        if ($id && null === $album) {
            throw $this->createNotFoundException('Album introuvable.');
        }

        $ina = $userRepository->findOneBy(['admin' => true]);

        if ($album) {
            $medias = $mediaRepository->findVisibleByAlbum($album);
        } else {
            $medias = $ina ? $mediaRepository->findByUser($ina) : [];
        }

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
