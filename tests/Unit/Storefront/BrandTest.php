<?php

declare(strict_types=1);

namespace SyliusStarter\Core\Tests\Unit\Storefront;

use Castor\Container;
use Castor\Runner\Parallel\JobRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SyliusStarter\Core\App;
use SyliusStarter\Core\Storefront\Brand;
use SyliusStarter\Core\Storefront\Hero;
use Symfony\Component\Console\Input\ArrayInput;
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
        $this->filesystem->dumpFile($this->root . '/config/packages/_sylius.yaml', <<<'YAML'
            imports:
                - { resource: "@SyliusCoreBundle/Resources/config/app/config.yml" }

            YAML);

        $container = (new \ReflectionClass(Container::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Container::class, 'fs'))->setValue($container, $this->filesystem);
        // Castor 1.9+ resolves io() and output() through the job registry of parallel().
        if (property_exists(Container::class, 'jobRegistry')) {
            (new \ReflectionProperty(Container::class, 'jobRegistry'))->setValue($container, new JobRegistry(new ArrayInput([])));
        }

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
        static::assertFileExists($this->root . '/templates/storefront/brand/logo.html.twig');
        static::assertFileExists($this->root . '/config/sylius/storefront/brand.yaml');
        static::assertDirectoryDoesNotExist($this->root . '/templates/bundles', 'Core must not override Sylius templates.');
    }

    public function testInstallIsIdempotent(): void
    {
        Brand::install($this->app);
        Brand::install($this->app);

        $config = (string) file_get_contents($this->root . '/config/packages/_sylius.yaml');

        static::assertSame(1, substr_count($config, Hero::CONFIG_IMPORT));
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
