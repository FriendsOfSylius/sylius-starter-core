<?php

declare(strict_types=1);

namespace Sylius\Starter\Core\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Starter\Core\Util\Runner;

#[CoversClass(Runner::class)]
final class RunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(Runner::MODE_ENV);
        putenv(Runner::PHP_BINARY_ENV);
        putenv(Runner::COMPOSER_BINARY_ENV);
    }

    public function testDockerIsTheDefaultMode(): void
    {
        static::assertSame(Runner::MODE_DOCKER, Runner::mode());
        static::assertFalse(Runner::isLocal());

        putenv(Runner::MODE_ENV . '=something-else');
        static::assertSame(Runner::MODE_DOCKER, Runner::mode());
    }

    public function testLocalModeIsEnabledByTheEnvironment(): void
    {
        putenv(Runner::MODE_ENV . '=Local');

        static::assertSame(Runner::MODE_LOCAL, Runner::mode());
        static::assertTrue(Runner::isLocal());
    }

    public function testLocalCommandUsesTheDefaultBinaries(): void
    {
        static::assertSame('php bin/console cache:clear -n', Runner::localCommand('php bin/console cache:clear -n'));
        static::assertSame('php bin/console doctrine:migrations:list', Runner::localCommand('bin/console doctrine:migrations:list'));
        static::assertSame('composer dump-autoload', Runner::localCommand('composer dump-autoload'));
        static::assertSame('yarn install', Runner::localCommand('yarn install'));
    }

    public function testLocalCommandUsesTheConfiguredBinaries(): void
    {
        putenv(Runner::PHP_BINARY_ENV . '=/usr/bin/php8.5');
        putenv(Runner::COMPOSER_BINARY_ENV . '=/usr/bin/php8.5 /usr/local/bin/composer');

        static::assertSame('/usr/bin/php8.5 bin/console sylius:fixtures:load import -n', Runner::localCommand('php bin/console sylius:fixtures:load import -n'));
        static::assertSame('/usr/bin/php8.5 bin/console assets:install', Runner::localCommand('bin/console assets:install'));
        static::assertSame('/usr/bin/php8.5 /usr/local/bin/composer dump-autoload', Runner::localCommand('composer dump-autoload'));
        static::assertSame('phpunit', Runner::localCommand('phpunit'), 'Only a "php " prefix is rewritten.');
    }
}
