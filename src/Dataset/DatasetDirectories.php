<?php

declare(strict_types=1);

namespace SyliusStarter\Core\Dataset;

/**
 * Packages shipping ready-made datasets (sylius-starter/datasets, ...) register their directory here, usually from a
 * file autoloaded by Composer. The import package then lists them without knowing the packages that provide them.
 *
 * Each sub-directory of a registered directory is a dataset: products.yaml, collections.yaml, dataset.yaml and media/.
 */
final class DatasetDirectories
{
    /** @var array<string, string> */
    private static array $directories = [];

    public static function register(string $name, string $directory): void
    {
        self::$directories[$name] = rtrim($directory, '/\\');
    }

    public static function unregister(string $name): void
    {
        unset(self::$directories[$name]);
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$directories;
    }
}
