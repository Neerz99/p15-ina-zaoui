<?php

namespace App\Service;

use App\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Déplace les fichiers envoyés dans public/uploads et les supprime du disque.
 * Le chemin stocké en base reste relatif à public/ (ex. « uploads/abc.jpg »).
 */
class MediaUploader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $publicDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function upload(Media $media): void
    {
        $file = $media->getFile();

        if (null === $file) {
            throw new \InvalidArgumentException('Aucun fichier à envoyer.');
        }

        $filename = bin2hex(random_bytes(16)).'.'.($file->guessExtension() ?? 'bin');
        $file->move($this->publicDir.'/uploads', $filename);
        $media->setPath('uploads/'.$filename);
        $media->setFile(null);
    }

    public function remove(Media $media): void
    {
        $path = $this->publicDir.'/'.$media->getPath();

        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }
}
