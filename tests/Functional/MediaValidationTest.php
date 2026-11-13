<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use App\Tests\Support\ImageFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MediaValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidImageIsAccepted(): void
    {
        self::assertCount(0, $this->validator->validate($this->media(ImageFactory::png())));
    }

    public function testFileIsRequired(): void
    {
        $violations = $this->validator->validate($this->media(null));

        self::assertCount(1, $violations);
        self::assertSame('Veuillez sélectionner une image.', $violations->get(0)->getMessage());
    }

    public function testNonImageIsRejectedByMimeType(): void
    {
        $violations = $this->validator->validate($this->media(ImageFactory::fakeImage()));

        self::assertGreaterThanOrEqual(1, \count($violations));
        self::assertStringContainsString('doit être une image', (string) $violations);
    }

    public function testImageOver2MbIsRejected(): void
    {
        $file = ImageFactory::bigJpeg();
        self::assertGreaterThan(2_000_000, $file->getSize());

        $violations = $this->validator->validate($this->media($file));

        self::assertStringContainsString('trop volumineux', (string) $violations);
    }

    public function testTitleIsRequired(): void
    {
        $media = $this->media(ImageFactory::png());
        $media->setTitle('');

        self::assertStringContainsString('Le titre est obligatoire.', (string) $this->validator->validate($media));
    }

    private function media(?\Symfony\Component\HttpFoundation\File\UploadedFile $file): Media
    {
        $media = new Media();
        $media->setTitle('Photo');
        $media->setFile($file);

        return $media;
    }
}
