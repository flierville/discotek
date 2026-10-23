<?php

declare(strict_types=1);

namespace App\Tests\Unit\Party;

use App\Party\PartyStorage;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class PartyStorageTest extends TestCase
{
    private string $dir;
    private PartyStorage $storage;

    protected function setUp(): void
    {
        $this->dir = TestImages::tempDir();
        $this->storage = new PartyStorage($this->dir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testEveryPartyHasItsOwnDirectory(): void
    {
        self::assertSame($this->dir.'/mariage-julie/photo-1.jpg', $this->storage->path('mariage-julie', 'photo-1.jpg'));
        self::assertSame($this->dir.'/mariage-julie/thumbs/photo-1.jpg', $this->storage->path('mariage-julie', 'photo-1.jpg', PartyStorage::SIZE_THUMB));
    }

    #[DataProvider('dangerousPaths')]
    public function testPathsCannotEscapeTheStorage(string $slug, string $filename): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->storage->path($slug, $filename);
    }

    /** @return iterable<string, array{string, string}> */
    public static function dangerousPaths(): iterable
    {
        yield 'parent directory in slug' => ['../config', 'a.jpg'];
        yield 'parent directory in filename' => ['party', '../../.env'];
        yield 'empty slug' => ['', 'a.jpg'];
    }

    public function testDirectoryFollowsTheSlugChange(): void
    {
        $this->storage->prepare('ancien-nom');
        file_put_contents($this->storage->path('ancien-nom', 'a.jpg'), 'x');

        $this->storage->rename('ancien-nom', 'nouveau-nom');

        self::assertFileExists($this->storage->path('nouveau-nom', 'a.jpg'));
        self::assertDirectoryDoesNotExist($this->storage->directory('ancien-nom'));
    }

    public function testRemovingThePartyDirectory(): void
    {
        $this->storage->prepare('a-supprimer');

        $this->storage->removeDirectory('a-supprimer');

        self::assertDirectoryDoesNotExist($this->storage->directory('a-supprimer'));
    }
}
