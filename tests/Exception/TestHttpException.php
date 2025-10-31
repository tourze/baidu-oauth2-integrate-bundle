<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Exception;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 测试用的 HTTP 异常实现。
 * 由于 Throwable 接口的方法（如 getMessage）是 final 的，无法通过 Mock 配置，
 * 因此使用此专用测试类来模拟 HttpExceptionInterface 行为。
 */
final class TestHttpException extends \RuntimeException implements HttpExceptionInterface
{
    private ResponseInterface $response;

    public function __construct(string $message, ResponseInterface $response, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
