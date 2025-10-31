<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;

#[WithMonologChannel(channel: 'baidu_oauth2_integrate')]
class BaiduApiClient
{
    private const DEFAULT_TIMEOUT = 30;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $requestOptions
     * @param array<string, mixed> $context
     * @return array{content: string, status_code: int}
     */
    public function makeRequest(string $operation, string $url, array $requestOptions, array $context = []): array
    {
        $fullContext = array_merge(['url' => $url], $context);
        $start = $this->logStart($operation, $fullContext);

        try {
            $response = $this->execute($url, $requestOptions);
            $this->logSuccess($operation, $start, $fullContext + ['status_code' => $response->getStatusCode()]);

            return ['content' => $response->getContent(), 'status_code' => $response->getStatusCode()];
        } catch (\Exception $e) {
            $this->logError($operation, $start, $e, $fullContext);
            $message = $e instanceof HttpExceptionInterface ? "Baidu API {$operation} HTTP error" : "Network error during {$operation}";
            throw new BaiduOAuth2Exception($message, previous: $e);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultHeaders(string $accept = 'application/json'): array
    {
        return [
            'User-Agent' => 'BaiduOAuth2IntegrateBundle/1.0',
            'Accept' => $accept,
        ];
    }

    /**
     * @param array<string, mixed> $requestOptions
     */
    private function execute(string $url, array $requestOptions): ResponseInterface
    {
        return $this->httpClient->request('GET', $url, array_merge([
            'timeout' => self::DEFAULT_TIMEOUT,
        ], $requestOptions));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logStart(string $operation, array $context): float
    {
        $this->logger?->info("Baidu OAuth2 {$operation} started", $context);

        return microtime(true);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logSuccess(string $operation, float $start, array $context): void
    {
        $duration = microtime(true) - $start;
        $this->logger?->info("Baidu OAuth2 {$operation} completed", $context + [
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logError(string $operation, float $start, \Exception $e, array $context): void
    {
        $duration = microtime(true) - $start;
        $log = $context + [
            'error' => $e->getMessage(),
            'duration_ms' => round($duration * 1000, 2),
        ];
        if ($e instanceof HttpExceptionInterface) {
            $log['status_code'] = $e->getResponse()->getStatusCode();
        }
        $this->logger?->error("Baidu OAuth2 {$operation} error", $log);
    }
}
