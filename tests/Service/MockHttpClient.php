<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class MockHttpClient implements HttpClientInterface
{
    private ?ResponseInterface $expectedResponse = null;

    private ?\Throwable $expectedException = null;

    /** @var array<callable(string, string, array<string, mixed>): void> */
    private array $expectedCallbacks = [];

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore-next-line method.childParameterType
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (null !== $this->expectedException) {
            throw $this->expectedException;
        }
        foreach ($this->expectedCallbacks as $callback) {
            $callback($method, $url, $options);
        }

        return $this->expectedResponse ?? $this->createDefaultResponse();
    }

    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        // 创建一个简单的匿名类实现
        return new class implements ResponseStreamInterface {
            public function key(): ResponseInterface
            {
                throw new \RuntimeException('Not implemented in mock');
            }

            public function current(): ChunkInterface
            {
                throw new \RuntimeException('Not implemented in mock');
            }

            public function next(): void
            {
                // Mock implementation
            }

            public function valid(): bool
            {
                return false;
            }

            public function rewind(): void
            {
                // Mock implementation
            }
        };
    }

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore-next-line method.childParameterType
     */
    public function withOptions(array $options): static
    {
        return $this;
    }

    public function setExpectedResponse(ResponseInterface $response): void
    {
        $this->expectedResponse = $response;
    }

    public function setExpectedException(\Throwable $exception): void
    {
        $this->expectedException = $exception;
    }

    /**
     * @param callable(string, string, array<string, mixed>): void $callback
     */
    public function addCallback(callable $callback): void
    {
        $this->expectedCallbacks[] = $callback;
    }

    public function clearExpectations(): void
    {
        $this->expectedResponse = null;
        $this->expectedException = null;
        $this->expectedCallbacks = [];
    }

    private function createDefaultResponse(): ResponseInterface
    {
        // 创建一个简单的匿名类实现
        return new class implements ResponseInterface {
            public function getStatusCode(): int
            {
                return 200;
            }

            public function getHeaders(bool $throw = true): array
            {
                return [];
            }

            public function getContent(bool $throw = true): string
            {
                return '{}';
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(bool $throw = true): array
            {
                return [];
            }

            public function cancel(): void
            {
                // Mock implementation
            }

            /**
             * @return mixed
             */
            public function getInfo(?string $type = null): mixed
            {
                return null;
            }
        };
    }
}
