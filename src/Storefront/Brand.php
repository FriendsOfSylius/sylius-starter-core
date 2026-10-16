<?php

declare(strict_types=1);

namespace Sylius\Starter\Core\Storefront;

use Sylius\Starter\Core\App;
use Symfony\Component\Finder\Finder;

use function Castor\fs;

/**
 * Shared storefront identity slot (the shop logo).
 *
 * Owned by core so that several packages can use it without conflicting:
 *  - themes provide the markup and read the identity with storefront_brand(),
 *    falling back on their own artwork;
 *  - other packages (import...) provide data through a BrandContributorInterface
 *    service generated in the application, never markup.
 *
 * Without a theme, the overridden @SyliusShop/shared/logo.html.twig renders the
 * contributed logo (header, checkout, error pages), otherwise the Sylius logo.
 *
 * install() is idempotent: every package needing the slot calls it.
 */
final readonly class Brand
{
    public const TWIG_FUNCTION = 'storefront_brand';

    public static function resourcesDir(): string
    {
        return \dirname(__DIR__, 2) . '/resources/storefront/brand';
    }

    public static function install(App $app): void
    {
        $resourcesDir = self::resourcesDir();

        foreach ((new Finder())->files()->in($resourcesDir) as $file) {
            // Core owns these files: always refresh them.
            fs()->copy($file->getPathname(), $app->directory() . '/' . $file->getRelativePathname(), true);
        }
    }

    public static function isInstalled(App $app): bool
    {
        return is_file($app->directory() . '/src/Twig/StorefrontBrandExtension.php');
    }
}
