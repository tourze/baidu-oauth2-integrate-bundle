<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\BaiduOauth2IntegrateBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOauth2IntegrateBundle::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOauth2IntegrateBundleTest extends AbstractBundleTestCase
{
}
