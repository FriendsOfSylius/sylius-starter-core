<?php

declare(strict_types=1);

namespace Sylius\Starter\Core\Tests\Unit\Storefront;

use Castor\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Starter\Core\App;
use Sylius\Starter\Core\Storefront\Brand;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Brand::class)]
final class BrandTest extends TestCase
{
    private string $root;

    private Filesystem $filesystem;

    private App $app;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/castor-brand-' . uniqid('', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->root);

        $container = (new \ReflectionClass(Container::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Container::class, 'fs'))->setValue($container, $this->filesystem);
        Container::set($container);

        $this->app = new App('app', $this->root);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testInstallCopiesTheSlotIntoTheApplication(): void
    {
        static::assertFalse(Brand::isInstalled($this->app));

        Brand::install($this->app);

        static::assertTrue(Brand::isInstalled($this->app));
        static::assertFileExists($this->root . '/src/Storefront/Brand/BrandContent.php');
        static::assertFileExists($this->root . '/src/Storefront/Brand/BrandContributorInterface.php');
        static::assertFileExists($this->root . '/templates/bundles/SyliusShopBundle/shared/logo.html.twig');
    }

    public function testInstallRefreshesCoreOwnedFiles(): void
    {
        Brand::install($this->app);

        $extension = $this->root . '/src/Twig/StorefrontBrandExtension.php';
        file_put_contents($extension, '<?php // stale');
        touch($extension, time() + 3600);

        Brand::install($this->app);

        static::assertStringContainsString('final class StorefrontBrandExtension', (string) file_get_contents($extension));
    }
}
