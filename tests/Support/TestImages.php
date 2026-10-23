<?php

declare(strict_types=1);

namespace App\Tests\Support;

/**
 * Creates real pictures for the upload tests.
 */
final class TestImages
{
    public static function jpeg(string $path, int $width = 2400, int $height = 1600): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, (int) imagecolorallocate($image, 255, 0, 225));
        imagejpeg($image, $path, 80);

        return $path;
    }

    public static function png(string $path, int $width = 300, int $height = 600): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);

        return $path;
    }

    /**
     * Zip with pictures, a nested folder, macOS metadata and a text file (ignored).
     */
    public static function zip(string $path, string $workDir): string
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile(self::jpeg($workDir.'/a.jpg', 800, 600), 'IMG_0001.JPG');
        $zip->addFile(self::png($workDir.'/b.png'), 'dossier/IMG_0002.png');
        $zip->addFromString('__MACOSX/._IMG_0001.JPG', 'metadata');
        $zip->addFromString('lisez-moi.txt', 'not a picture');
        $zip->addFromString('../../evil.jpg', 'zip slip attempt');
        $zip->close();

        return $path;
    }

    public static function tempDir(): string
    {
        $dir = sys_get_temp_dir().'/discotek-tests-'.bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);

        return $dir;
    }
}
