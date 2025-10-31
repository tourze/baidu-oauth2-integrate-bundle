<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Exception;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 测试用的传输异常实现。
 * 由于 Throwable 接口的方法（如 getMessage）是 final 的，无法通过 Mock 配置，
 * 因此使用此专用测试类来模拟 TransportExceptionInterface 行为。
 */
final class TestTransportException extends \RuntimeException implements TransportExceptionInterface
{
}
