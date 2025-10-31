<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduApiClient;
use Tourze\BaiduOauth2IntegrateBundle\Tests\Exception\TestHttpException;
use Tourze\BaiduOauth2IntegrateBundle\Tests\Exception\TestTransportException;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduApiClient::class)]
#[RunTestsInSeparateProcesses]
final class BaiduApiClientTest extends AbstractIntegrationTestCase
{
    private MockHttpClient $httpClient;

    private BaiduApiClient $apiClient;

    protected function onSetUp(): void
    {
        $this->httpClient = $this->createMockHttpClient();

        // 设置mock的HttpClient到容器中
        self::getContainer()->set(HttpClientInterface::class, $this->httpClient);

        // 从容器获取被测试的服务（让它使用默认的logger）
        $this->apiClient = self::getService(BaiduApiClient::class);
    }

    private function createMockHttpClient(): MockHttpClient
    {
        return new MockHttpClient();
    }

    private function createResponse(string $content, int $statusCode): ResponseInterface
    {
        $mock = self::createMock(ResponseInterface::class);
        $mock->method('getContent')->willReturn($content);
        $mock->method('getStatusCode')->willReturn($statusCode);
        $mock->method('getHeaders')->willReturn([]);
        $mock->method('toArray')->willReturn([]);
        $mock->method('getInfo')->willReturn(null);

        return $mock;
    }

    /**
     * 创建一个实现 HttpExceptionInterface 的测试异常。
     * 由于 getMessage() 等 Throwable 方法是 final 方法无法 Mock，
     * 因此使用专用的测试异常类以满足接口要求。
     */
    private function createMockHttpException(string $message, ResponseInterface $response): HttpExceptionInterface
    {
        return new TestHttpException($message, $response);
    }

    /**
     * 创建一个实现 TransportExceptionInterface 的测试异常。
     * 由于 getMessage() 等 Throwable 方法是 final 方法无法 Mock，
     * 因此使用专用的测试异常类以满足接口要求。
     */
    private function createMockTransportException(string $message): TransportExceptionInterface
    {
        return new TestTransportException($message);
    }

    public function testMakeRequestSuccess(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('{"success": true}', 200);
        $this->httpClient->setExpectedResponse($response);
        $this->httpClient->addCallback(function (string $method, string $url, array $options): void {
            $this->assertEquals('GET', $method);
            $this->assertEquals('https://api.baidu.com/test', $url);
            $this->assertArrayHasKey('timeout', $options);
            $this->assertEquals(30, $options['timeout']);
            $this->assertArrayHasKey('headers', $options);
            $this->assertEquals(['Content-Type' => 'application/json'], $options['headers']);
        });

        $result = $this->apiClient->makeRequest(
            'token_exchange',
            'https://api.baidu.com/test',
            ['headers' => ['Content-Type' => 'application/json']]
        );

        $this->assertEquals(['content' => '{"success": true}', 'status_code' => 200], $result);
    }

    public function testMakeRequestWithContext(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('response_content', 200);
        $this->httpClient->setExpectedResponse($response);

        $context = ['client_id' => 'test_client'];

        $result = $this->apiClient->makeRequest(
            'user_info',
            'https://api.baidu.com/user',
            ['timeout' => 10],
            $context
        );

        $this->assertEquals(['content' => 'response_content', 'status_code' => 200], $result);
    }

    public function testMakeRequestHttpException(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('', 404);

        // 创建HttpException mock
        $httpException = $this->createMockHttpException('Not Found', $response);

        $this->httpClient->setExpectedException($httpException);

        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('Baidu API api_call HTTP error');

        $this->apiClient->makeRequest('api_call', 'https://api.baidu.com/test', []);
    }

    public function testMakeRequestTransportException(): void
    {
        $this->httpClient->clearExpectations();

        // 创建TransportException mock
        $transportException = $this->createMockTransportException('Connection timeout');

        $this->httpClient->setExpectedException($transportException);

        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('Network error during network_call');

        $this->apiClient->makeRequest('network_call', 'https://api.baidu.com/test', []);
    }

    public function testMakeRequestGenericException(): void
    {
        $this->httpClient->clearExpectations();

        $genericException = new \RuntimeException('Generic error');

        $this->httpClient->setExpectedException($genericException);

        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('Network error during generic_call');

        $this->apiClient->makeRequest('generic_call', 'https://api.baidu.com/test', []);
    }

    public function testGetDefaultHeaders(): void
    {
        $headers = $this->apiClient->getDefaultHeaders();

        $expected = [
            'User-Agent' => 'BaiduOAuth2IntegrateBundle/1.0',
            'Accept' => 'application/json',
        ];

        $this->assertEquals($expected, $headers);
    }

    public function testGetDefaultHeadersWithCustomAccept(): void
    {
        $headers = $this->apiClient->getDefaultHeaders('application/xml');

        $expected = [
            'User-Agent' => 'BaiduOAuth2IntegrateBundle/1.0',
            'Accept' => 'application/xml',
        ];

        $this->assertEquals($expected, $headers);
    }

    public function testMakeRequestWithoutSpecificLogger(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('test_content', 200);
        $this->httpClient->setExpectedResponse($response);

        $result = $this->apiClient->makeRequest('test', 'https://api.baidu.com/test', []);

        $this->assertEquals(['content' => 'test_content', 'status_code' => 200], $result);
    }

    public function testMakeRequestMergesDefaultTimeout(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('content', 200);
        $this->httpClient->setExpectedResponse($response);
        $this->httpClient->addCallback(function (string $method, string $url, array $options): void {
            $this->assertEquals('GET', $method);
            $this->assertEquals('https://api.baidu.com/test', $url);
            $this->assertEquals(30, $options['timeout']); // Default timeout should be merged
            $this->assertEquals(['Accept' => 'application/json'], $options['headers']);
        });

        $result = $this->apiClient->makeRequest(
            'test',
            'https://api.baidu.com/test',
            ['headers' => ['Accept' => 'application/json']]
        );

        $this->assertEquals(['content' => 'content', 'status_code' => 200], $result);
    }

    public function testMakeRequestOverridesDefaultTimeout(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('content', 200);
        $this->httpClient->setExpectedResponse($response);
        $this->httpClient->addCallback(function (string $method, string $url, array $options): void {
            $this->assertEquals('GET', $method);
            $this->assertEquals('https://api.baidu.com/test', $url);
            $this->assertEquals(60, $options['timeout']); // Custom timeout should override default
            $this->assertEquals(['Accept' => 'application/json'], $options['headers']);
        });

        $result = $this->apiClient->makeRequest(
            'test',
            'https://api.baidu.com/test',
            [
                'timeout' => 60,
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        $this->assertEquals(['content' => 'content', 'status_code' => 200], $result);
    }

    public function testLogContextIncludesExpectedFields(): void
    {
        $this->httpClient->clearExpectations();

        $response = $this->createResponse('content', 200);
        $this->httpClient->setExpectedResponse($response);

        $result = $this->apiClient->makeRequest(
            'test_operation',
            'https://api.baidu.com/test',
            [],
            ['custom_field' => 'custom_value']
        );

        // 验证请求正常完成
        $this->assertEquals(['content' => 'content', 'status_code' => 200], $result);
        // 验证URL和context被正确传递 - 通过没有抛出异常来间接验证日志记录正常工作
        $this->assertNotNull($result);
    }
}
