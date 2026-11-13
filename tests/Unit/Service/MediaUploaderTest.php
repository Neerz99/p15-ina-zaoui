<?php

namespace App\Tests\Unit\Service;

use App\Entity\Media;
use App\Service\MediaUploader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploaderTest extends TestCase
{
    private string $publicDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->publicDir = sys_get_temp_dir().'/p15-uploader-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir($this->publicDir.'/uploads');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->publicDir);
    }

    public function testUploadMovesFileWithRandomNameAndStoresRelativePath(): void
    {
        $source = $this->publicDir.'/source.png';
        imagepng(imagecreatetruecolor(10, 10), $source);

        $media = new Media();
        $media->setFile(new UploadedFile($source, 'photo.png', 'image/png', null, true));

        (new MediaUploader($this->publicDir))->upload($media);

        self::assertMatchesRegularExpression('#^uploads/[a-f0-9]{32}\.png$#', $media->getPath());
        self::assertFileExists($this->publicDir.'/'.$media->getPath());
        self::assertNull($media->getFile());
    }

    public function testUploadWithoutFileThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new MediaUploader($this->publicDir))->upload(new Media());
    }

    public function testRemoveDeletesFileAndIgnoresMissingOnes(): void
    {
        $this->filesystem->dumpFile($this->publicDir.'/uploads/a.jpg', 'x');
        $uploader = new MediaUploader($this->publicDir);

        $media = new Media();
        $media->setPath('uploads/a.jpg');
        $uploader->remove($media);
        self::assertFileDoesNotExist($this->publicDir.'/uploads/a.jpg');

        $uploader->remove($media);
        $this->addToAssertionCount(1);
    }
}
