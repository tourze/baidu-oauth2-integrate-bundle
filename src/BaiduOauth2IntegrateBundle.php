<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

class BaiduOauth2IntegrateBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
        ];
    }
}
