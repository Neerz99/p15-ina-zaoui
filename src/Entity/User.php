<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $admin = false;

    /**
     * Invité dont Ina a révoqué l'accès : connexion impossible et photos masquées.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $blocked = false;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'adresse e-mail est obligatoire.")]
    #[Assert\Email(message: "L'adresse e-mail n'est pas valide.")]
    #[Assert\Length(max: 180)]
    private string $email = '';

    /**
     * Mot de passe hashé (jamais en clair).
     */
    #[ORM\Column]
    private string $password = '';

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $medias;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    /**
     * @param Collection<int, Media> $medias
     */
    public function setMedias(Collection $medias): static
    {
        $this->medias = $medias;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): static
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Identifiant de connexion : l'adresse e-mail.
     */
    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('Un utilisateur doit avoir une adresse e-mail.');
        }

        return $this->email;
    }

    /**
     * Les rôles sont déduits du booléen « admin » déjà présent en base :
     * Ina est ROLE_ADMIN, les invités sont ROLE_USER.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->admin ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * Appelée à chaque requête pour comparer l'utilisateur en session ($this)
     * à sa version rechargée depuis la base : si Ina bloque un invité,
     * sa session est invalidée immédiatement.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self || $user->isBlocked()) {
            return false;
        }

        $refreshedPassword = $user->getPassword();

        return $this->id === $user->getId()
            && $this->email === $user->getEmail()
            && $this->admin === $user->isAdmin()
            && \in_array($this->password, [$refreshedPassword, hash('crc32c', $refreshedPassword)], true);
    }

    /**
     * Ne sérialise pas le hash complet en session (bonne pratique Symfony 7.3+).
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
