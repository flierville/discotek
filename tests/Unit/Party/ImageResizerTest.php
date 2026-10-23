<?php

declare(strict_types=1);

namespace App\Tests\Unit\Party;

use App\Party\ImageResizer;
use App\Party\UnsupportedImageException;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ImageResizerTest extends TestCase
{
    private string $dir;
    private ImageResizer $resizer;

    protected function setUp(): void
    {
        $this->dir = TestImages::tempDir();
        $this->resizer = new ImageResizer();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testPictureIsResizedForEveryTarget(): void
    {
        $source = TestImages::jpeg($this->dir.'/source.jpg', 3000, 2000);

        $dimensions = $this->resizer->resize($source, [
            $this->dir.'/large.jpg' => 1600,
            $this->dir.'/thumb.jpg' => 480,
        ]);

        self::assertSame([1600, 1067], $dimensions);
        self::assertSame([1600, 1067], \array_slice(getimagesize($this->dir.'/large.jpg') ?: [], 0, 2));
        self::assertSame([480, 320], \array_slice(getimagesize($this->dir.'/thumb.jpg') ?: [], 0, 2));
    }

    public function testSmallPicturesAreNeverUpscaled(): void
    {
        self::assertSame([300, 200], $this->resizer->fit(300, 200, 1600));
        self::assertSame([240, 480], $this->resizer->fit(300, 600, 480));
    }

    public function testPngIsConvertedToJpeg(): void
    {
        $source = TestImages::png($this->dir.'/source.png');

        $this->resizer->resize($source, [$this->dir.'/out.jpg' => 400]);

        self::assertSame(IMAGETYPE_JPEG, (getimagesize($this->dir.'/out.jpg') ?: [])[2]);
    }

    public function testFilesWhichAreNotPicturesAreRejected(): void
    {
        file_put_contents($this->dir.'/fake.jpg', '<?php echo "not a picture";');

        self::assertFalse($this->resizer->supports($this->dir.'/fake.jpg'));
        $this->expectException(UnsupportedImageException::class);
        $this->resizer->resize($this->dir.'/fake.jpg', [$this->dir.'/out.jpg' => 400]);
    }
}
