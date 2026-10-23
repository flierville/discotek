<?php

declare(strict_types=1);

namespace App\Party;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Rebuilds a file sent in several chunks (big zip archives exceed upload_max_filesize).
 * Chunks must be sent in order; the assembled file is returned with the last chunk.
 */
final readonly class ChunkedUploadReceiver
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads')]
        private string $tmpDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @return string|null path of the complete file, null while chunks are missing
     */
    public function receive(UploadedFile $chunk, string $uploadId, int $chunkIndex, int $chunkCount): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $uploadId) || $chunkIndex < 0 || $chunkCount < 1 || $chunkIndex >= $chunkCount) {
            throw new \InvalidArgumentException('Invalid chunk metadata.');
        }

        if (!$chunk->isValid()) {
            throw new \RuntimeException($chunk->getErrorMessage());
        }

        $this->filesystem->mkdir($this->tmpDir);
        $partPath = $this->tmpDir.'/'.$uploadId.'.part';

        if (0 === $chunkIndex) {
            $this->filesystem->remove($partPath);
        } elseif (!is_file($partPath)) {
            throw new \RuntimeException('Missing previous chunks.');
        }

        $this->filesystem->appendToFile($partPath, (string) file_get_contents($chunk->getPathname()));

        return $chunkIndex === $chunkCount - 1 ? $partPath : null;
    }

    public function cleanup(string $path): void
    {
        $this->filesystem->remove($path);
    }
}
