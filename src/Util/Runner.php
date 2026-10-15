<?php

declare(strict_types=1);

namespace Sylius\Starter\Core\Util;

use Sylius\Starter\Core\App;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\Docker\docker_compose_run;
use function Castor\run;

/**
 * Runs a command against the Sylius application.
 *
 * SYLIUS_STARTER_RUNNER selects where:
 * - "docker" (default): in the "{app}-builder" Docker Compose service;
 * - "local": directly in the application directory, with the PHP and Composer
 *   binaries from SYLIUS_STARTER_PHP_BINARY / SYLIUS_STARTER_COMPOSER_BINARY.
 *   Used when Castor itself runs next to the application (no Docker in production,
 *   or inside a container that already has the application runtime).
 */
final readonly class Runner
{
    public const string MODE_ENV = 'SYLIUS_STARTER_RUNNER';
    public const string PHP_BINARY_ENV = 'SYLIUS_STARTER_PHP_BINARY';
    public const string COMPOSER_BINARY_ENV = 'SYLIUS_STARTER_COMPOSER_BINARY';

    public const string MODE_DOCKER = 'docker';
    public const string MODE_LOCAL = 'local';

    public static function mode(): string
    {
        $mode = strtolower(trim((string) getenv(self::MODE_ENV)));

        return self::MODE_LOCAL === $mode ? self::MODE_LOCAL : self::MODE_DOCKER;
    }

    public static function isLocal(): bool
    {
        return self::MODE_LOCAL === self::mode();
    }

    public static function run(App $app, string $command): Process
    {
        if (!self::isLocal()) {
            return docker_compose_run($command, $app->name() . '-builder');
        }

        return run(
            self::localCommand($command),
            context: context()->withWorkingDirectory(self::localAppDirectory($app)),
        );
    }

    /**
     * Commands are written for the builder container ("php bin/console …",
     * "bin/console …", "composer …"): point them at the configured binaries.
     */
    public static function localCommand(string $command): string
    {
        $command = ltrim($command);
        $php = self::binary(self::PHP_BINARY_ENV, 'php');

        if (str_starts_with($command, 'php ')) {
            return $php . substr($command, 3);
        }

        if (str_starts_with($command, 'bin/console')) {
            return $php . ' ' . $command;
        }

        if (str_starts_with($command, 'composer ')) {
            return self::binary(self::COMPOSER_BINARY_ENV, 'composer') . substr($command, 8);
        }

        return $command;
    }

    public static function localAppDirectory(App $app): string
    {
        $directory = $app->directory();

        if (str_starts_with($directory, '/')) {
            return $directory;
        }

        return rtrim(context()->workingDirectory, '/') . '/' . $directory;
    }

    private static function binary(string $env, string $default): string
    {
        $value = trim((string) getenv($env));

        return '' !== $value ? $value : $default;
    }
}
