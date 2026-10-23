<?php

declare(strict_types=1);

namespace App\Tests\Unit\Party;

use App\Party\ChunkedUploadReceiver;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ChunkedUploadReceiverTest extends TestCase
{
    private string $dir;
    private ChunkedUploadReceiver $receiver;

    protected function setUp(): void
    {
        $this->dir = TestImages::tempDir();
        $this->receiver = new ChunkedUploadReceiver($this->dir.'/uploads');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testChunksAreAssembledInOrder(): void
    {
        self::assertNull($this->receiver->receive($this->chunk('AAA'), 'upload-123', 0, 3));
        self::assertNull($this->receiver->receive($this->chunk('BBB'), 'upload-123', 1, 3));
        $path = $this->receiver->receive($this->chunk('CC'), 'upload-123', 2, 3);

        self::assertNotNull($path);
        self::assertSame('AAABBBCC', file_get_contents($path));

        $this->receiver->cleanup($path);
        self::assertFileDoesNotExist($path);
    }

    public function testSingleChunkFileIsCompleteImmediately(): void
    {
        $path = $this->receiver->receive($this->chunk('photo'), 'upload-single', 0, 1);

        self::assertSame('photo', file_get_contents((string) $path));
    }

    public function testUploadIdCannotBeAPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->receiver->receive($this->chunk('x'), '../../config', 0, 1);
    }

    public function testChunkIndexMustBeInRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->receiver->receive($this->chunk('x'), 'upload-123', 3, 3);
    }

    public function testChunkWithoutThePreviousOnesIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->receiver->receive($this->chunk('x'), 'upload-orphan', 1, 2);
    }

    private function chunk(string $content): UploadedFile
    {
        $path = tempnam($this->dir, 'chunk');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'photo.zip', test: true);
    }
}
