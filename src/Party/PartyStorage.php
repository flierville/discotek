<?php

declare(strict_types=1);

namespace App\Party;

use App\Entity\Party;
use App\Entity\PartyPhoto;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Party photos live outside the public directory, one directory per party (named after its slug):
 *   {storageDir}/{slug}/{filename}          large picture (max 1600 px)
 *   {storageDir}/{slug}/thumbs/{filename}   thumbnail
 * They are served by a controller which checks the access code.
 */
final readonly class PartyStorage
{
    public const SIZE_LARGE = 'large';
    public const SIZE_THUMB = 'thumb';

    public function __construct(
        #[Autowire('%app.party_storage_dir%')]
        private string $storageDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function directory(string $slug): string
    {
        if (!preg_match('/^'.Party::SLUG_PATTERN.'$/', $slug)) {
            throw new \InvalidArgumentException(\sprintf('Invalid party slug "%s".', $slug));
        }

        return $this->storageDir.'/'.$slug;
    }

    public function path(string $slug, string $filename, string $size = self::SIZE_LARGE): string
    {
        if (basename($filename) !== $filename) {
            throw new \InvalidArgumentException(\sprintf('Invalid photo filename "%s".', $filename));
        }

        return match ($size) {
            self::SIZE_LARGE => $this->directory($slug).'/'.$filename,
            self::SIZE_THUMB => $this->directory($slug).'/thumbs/'.$filename,
            default => throw new \InvalidArgumentException(\sprintf('Unknown photo size "%s".', $size)),
        };
    }

    public function photoPath(PartyPhoto $photo, string $size = self::SIZE_LARGE): string
    {
        return $this->path($photo->getParty()->getSlug(), $photo->getFilename(), $size);
    }

    public function prepare(string $slug): void
    {
        $this->filesystem->mkdir($this->directory($slug).'/thumbs');
    }

    public function rename(string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === $newSlug || !is_dir($this->directory($oldSlug))) {
            return;
        }

        if (is_dir($this->directory($newSlug))) {
            throw new \RuntimeException(\sprintf('The directory of party "%s" already exists.', $newSlug));
        }

        $this->filesystem->rename($this->directory($oldSlug), $this->directory($newSlug));
    }

    public function removeDirectory(string $slug): void
    {
        $this->filesystem->remove($this->directory($slug));
    }

    public function removePhoto(PartyPhoto $photo): void
    {
        $this->filesystem->remove([
            $this->photoPath($photo, self::SIZE_LARGE),
            $this->photoPath($photo, self::SIZE_THUMB),
        ]);
    }
}
