<?php

declare(strict_types=1);

namespace App\Party;

use App\Entity\Party;
use App\Entity\PartyPhoto;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Adds pictures (single files or zip archives) to a party: resizes them into the party
 * directory and records them in the database.
 */
final class PhotoImporter
{
    public const LARGE_MAX_SIZE = 1600;
    public const THUMB_MAX_SIZE = 480;
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_ARCHIVE_ENTRIES = 3000;

    private readonly AsciiSlugger $slugger;

    public function __construct(
        private readonly PartyStorage $storage,
        private readonly ImageResizer $resizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        $this->slugger = new AsciiSlugger('fr');
    }

    public static function isArchive(string $originalName): bool
    {
        return 'zip' === mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }

    /**
     * Imports a picture or every picture of a zip archive. Flushes the entity manager.
     */
    public function import(Party $party, string $path, string $originalName): ImportResult
    {
        @set_time_limit(0);
        $result = self::isArchive($originalName)
            ? $this->importArchive($party, $path)
            : $this->importSingle($party, $path, $originalName);

        $this->entityManager->flush();

        return $result;
    }

    private function importSingle(Party $party, string $path, string $originalName): ImportResult
    {
        $result = new ImportResult();

        try {
            $result->added[] = $this->addPhoto($party, $path, $originalName);
        } catch (UnsupportedImageException $exception) {
            $this->logger->notice('Party photo rejected: {message}', ['message' => $exception->getMessage()]);
            $result->rejected[] = $originalName;
        }

        return $result;
    }

    private function importArchive(Party $party, string $path): ImportResult
    {
        $zip = new \ZipArchive();
        if (true !== $zip->open($path, \ZipArchive::RDONLY)) {
            throw new UnsupportedImageException('The zip archive cannot be opened.');
        }

        $result = new ImportResult();
        $tmpFile = tempnam(sys_get_temp_dir(), 'party_');

        try {
            $count = min($zip->numFiles, self::MAX_ARCHIVE_ENTRIES);
            for ($index = 0; $index < $count; ++$index) {
                $entryName = (string) $zip->getNameIndex($index);
                if (!$this->isPictureEntry($entryName)) {
                    continue;
                }

                // The entry is copied to a temporary file: its name is never used as a path (no zip slip).
                $stream = $zip->getStream($entryName);
                if (false === $stream) {
                    $result->rejected[] = $entryName;
                    continue;
                }
                file_put_contents($tmpFile, $stream);
                fclose($stream);

                try {
                    $result->added[] = $this->addPhoto($party, $tmpFile, basename($entryName));
                } catch (UnsupportedImageException) {
                    $result->rejected[] = basename($entryName);
                }
            }
        } finally {
            $zip->close();
            @unlink($tmpFile);
        }

        return $result;
    }

    private function isPictureEntry(string $entryName): bool
    {
        $basename = basename($entryName);

        return !str_ends_with($entryName, '/')
            && !str_starts_with($entryName, '__MACOSX/')
            && !str_starts_with($basename, '.')
            && \in_array(mb_strtolower(pathinfo($basename, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
    }

    private function addPhoto(Party $party, string $sourcePath, string $originalName): PartyPhoto
    {
        $this->storage->prepare($party->getSlug());
        $filename = $this->uniqueFilename($originalName);

        [$width, $height] = $this->resizer->resize($sourcePath, [
            $this->storage->path($party->getSlug(), $filename, PartyStorage::SIZE_LARGE) => self::LARGE_MAX_SIZE,
            $this->storage->path($party->getSlug(), $filename, PartyStorage::SIZE_THUMB) => self::THUMB_MAX_SIZE,
        ]);

        $photo = new PartyPhoto($party, $filename, mb_substr($originalName, 0, 255), $width, $height);
        $this->entityManager->persist($photo);

        return $photo;
    }

    private function uniqueFilename(string $originalName): string
    {
        $base = $this->slugger->slug(pathinfo($originalName, PATHINFO_FILENAME))->lower()->truncate(100)->toString() ?: 'photo';

        return \sprintf('%s-%s.jpg', $base, bin2hex(random_bytes(4)));
    }
}
