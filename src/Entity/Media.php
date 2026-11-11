<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "medias", fetch: "EAGER")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Album::class, fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Album $album = null;

    #[ORM\Column]
    private string $path;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $title;

    /**
     * Fichier envoyé via le formulaire (non persisté).
     * Le type est vérifié par son type MIME réel (et non par l'extension) et la taille est limitée à 2 Mo.
     */
    #[Assert\NotNull(message: 'Veuillez sélectionner une image.')]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est de {{ limit }} {{ suffix }}.',
        mimeTypesMessage: 'Le fichier doit être une image (JPEG, PNG, WebP ou GIF).',
        uploadIniSizeErrorMessage: 'Le fichier est trop volumineux. La taille maximale autorisée est de 2 Mo.',
    )]
    private ?UploadedFile $file = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): void
    {
        $this->file = $file;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): void
    {
        $this->album = $album;
    }
}
