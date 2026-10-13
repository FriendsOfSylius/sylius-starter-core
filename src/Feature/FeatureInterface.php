<?php

declare(strict_types=1);

namespace Sylius\Starter\Core\Feature;

use Sylius\Starter\Core\App;

interface FeatureInterface
{
    public function name(): string;

    public function description(): string;

    public function __invoke(App $app): void;
}
