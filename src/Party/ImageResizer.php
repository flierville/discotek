<?php

declare(strict_types=1);

namespace App\Party;

/**
 * Resizes pictures with GD into JPEG files, honouring the EXIF orientation of camera photos.
 */
final class ImageResizer
{
    private const SUPPORTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    public function __construct(private readonly int $jpegQuality = 82)
    {
    }

    public function supports(string $path): bool
    {
        $info = @getimagesize($path);

        return false !== $info && \in_array($info[2], self::SUPPORTED_TYPES, true);
    }

    /**
     * Writes one JPEG per requested size, never upscaling.
     *
     * @param array<string, int> $targets target path => max size (longest side in px)
     *
     * @return array{int, int} width and height of the first target
     */
    public function resize(string $sourcePath, array $targets): array
    {
        $image = $this->load($sourcePath);
        $dimensions = null;

        foreach ($targets as $targetPath => $maxSize) {
            [$width, $height] = $this->fit(imagesx($image), imagesy($image), $maxSize);
            $resized = imagecreatetruecolor($width, $height);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
            $this->save($resized, $targetPath);
            $dimensions ??= [$width, $height];
        }

        if (null === $dimensions) {
            throw new \InvalidArgumentException('At least one target is required.');
        }

        return $dimensions;
    }

    /** @return array{int, int} */
    public function fit(int $width, int $height, int $maxSize): array
    {
        $ratio = min(1, $maxSize / max($width, $height));

        return [max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))];
    }

    private function load(string $path): \GdImage
    {
        $info = @getimagesize($path);
        if (false === $info || !\in_array($info[2], self::SUPPORTED_TYPES, true)) {
            throw new UnsupportedImageException(\sprintf('"%s" is not a supported picture.', basename($path)));
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };

        if (false === $image) {
            throw new UnsupportedImageException(\sprintf('"%s" could not be decoded.', basename($path)));
        }

        // Flatten transparency on a white background (JPEG output).
        $flattened = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flattened, 0, 0, (int) imagecolorallocate($flattened, 255, 255, 255));
        imagecopy($flattened, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return IMAGETYPE_JPEG === $info[2] ? $this->applyExifOrientation($flattened, $path) : $flattened;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        $exif = \function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $orientation = \is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        return $rotated instanceof \GdImage ? $rotated : $image;
    }

    private function save(\GdImage $image, string $targetPath): void
    {
        imageinterlace($image, true);
        if (!imagejpeg($image, $targetPath, $this->jpegQuality)) {
            throw new \RuntimeException(\sprintf('Unable to write "%s".', $targetPath));
        }
    }
}
