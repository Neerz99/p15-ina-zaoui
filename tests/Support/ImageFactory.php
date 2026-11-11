<?php

namespace App\Tests\Support;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fabrique de fichiers temporaires pour tester l'upload.
 */
final class ImageFactory
{
    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    public static function png(int $width = 20, int $height = 20): UploadedFile
    {
        $path = self::tempPath('png');
        imagepng(imagecreatetruecolor($width, $height), $path);

        return new UploadedFile($path, 'photo.png', 'image/png', null, true);
    }

    /**
     * Fichier JPEG valide de plus de 2 Mo (bruit aléatoire, très peu compressible).
     */
    public static function bigJpeg(): UploadedFile
    {
        $path = self::tempPath('jpg');
        $image = imagecreatetruecolor(1600, 1600);
        for ($y = 0; $y < 1600; ++$y) {
            for ($x = 0; $x < 1600; ++$x) {
                imagesetpixel($image, $x, $y, random_int(0, 0xFFFFFF));
            }
        }
        imagejpeg($image, $path, 100);

        return new UploadedFile($path, 'grosse-photo.jpg', 'image/jpeg', null, true);
    }

    /**
     * Fichier texte déguisé avec une extension .jpg : le type MIME réel est vérifié.
     */
    public static function fakeImage(): UploadedFile
    {
        $path = self::tempPath('jpg');
        file_put_contents($path, '<?php echo "pas une image"; ?>');

        return new UploadedFile($path, 'faux.jpg', 'image/jpeg', null, true);
    }

    private static function tempPath(string $extension): string
    {
        return sys_get_temp_dir().'/p15-'.bin2hex(random_bytes(6)).'.'.$extension;
    }
}
