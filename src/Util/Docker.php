<?php

declare(strict_types=1);

namespace SyliusStarter\Core\Util;

use SyliusStarter\Core\App;
use Symfony\Component\Process\Process;

final readonly class Docker
{
    /**
     * Runs a command in the application builder: Docker by default, or directly
     * on the host when SYLIUS_STARTER_RUNNER=local (see Runner).
     */
    public static function run(App $app, string $command): Process
    {
        return Runner::run($app, $command);
    }
}
