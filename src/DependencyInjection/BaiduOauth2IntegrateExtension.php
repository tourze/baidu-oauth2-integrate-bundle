<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class BaiduOauth2IntegrateExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
