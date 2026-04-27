<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MediaUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

final class MediaUrlGeneratorTest extends TestCase
{
    public function testGenerateReturnsEmptyStringForEmptyPath(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->expects($this->never())->method('getUrl');

        $generator = new MediaUrlGenerator($packages, '');

        self::assertSame('', $generator->generate(null));
        self::assertSame('', $generator->generate(' / '));
    }

    #[DataProvider('absolutePathProvider')]
    public function testGenerateReturnsAbsolutePathAsIs(string $path): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->expects($this->never())->method('getUrl');

        $generator = new MediaUrlGenerator($packages, '');

        self::assertSame($path, $generator->generate($path));
    }

    public static function absolutePathProvider(): iterable
    {
        yield 'https url' => ['https://example.com/img.jpg'];
        yield 'http url' => ['http://example.com/img.jpg'];
        yield 'protocol-relative url' => ['//cdn.example.com/img.jpg'];
        yield 'data uri' => ['data:image/png;base64,AAA'];
    }

    public function testGenerateUsesAssetsPackageWhenBaseUrlIsEmpty(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('images/work.jpg')
            ->willReturn('/images/work.jpg');

        $generator = new MediaUrlGenerator($packages, '');

        self::assertSame('/images/work.jpg', $generator->generate('/images/work.jpg'));
    }

    public function testGenerateUsesConfiguredBaseUrlWhenProvided(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->expects($this->never())->method('getUrl');

        $generator = new MediaUrlGenerator($packages, 'https://cdn.example.com/media/');

        self::assertSame(
            'https://cdn.example.com/media/images/work.jpg',
            $generator->generate('/images/work.jpg'),
        );
    }
}
