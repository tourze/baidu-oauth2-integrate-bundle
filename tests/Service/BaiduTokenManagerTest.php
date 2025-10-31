<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduApiClient;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduTokenManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduTokenManager::class)]
#[RunTestsInSeparateProcesses]
final class BaiduTokenManagerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无额外设置需求
    }

    public function testExchangeCodeForTokenParsesJsonResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $apiClient = new class($httpClient) extends BaiduApiClient {
            public function __construct(HttpClientInterface $httpClient)
            {
                parent::__construct($httpClient, null);
            }

            public function getDefaultHeaders(string $accept = 'application/json'): array
            {
                return [];
            }

            public function makeRequest(string $operation, string $url, array $requestOptions, array $context = []): array
            {
                return [
                    'content' => json_encode([
                        'access_token' => 'token_123',
                        'expires_in' => 3600,
                        'refresh_token' => 'refresh_456',
                    ], JSON_THROW_ON_ERROR),
                    'status_code' => 200,
                ];
            }
        };

        self::getContainer()->set(BaiduApiClient::class, $apiClient);
        $manager = self::getContainer()->get(BaiduTokenManager::class);
        $this->assertInstanceOf(BaiduTokenManager::class, $manager);
        $data = $manager->exchangeCodeForToken('code', 'cid', 'sec', 'http://example/callback');

        $this->assertEquals('token_123', $data['access_token']);
        $this->assertEquals(3600, $data['expires_in']);
        $this->assertEquals('refresh_456', $data['refresh_token']);
    }

    public function testRefreshTokenUpdatesUser(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $apiClient = new class($httpClient) extends BaiduApiClient {
            public function __construct(HttpClientInterface $httpClient)
            {
                parent::__construct($httpClient, null);
            }

            public function getDefaultHeaders(string $accept = 'application/json'): array
            {
                return [];
            }

            public function makeRequest(string $operation, string $url, array $requestOptions, array $context = []): array
            {
                return [
                    'content' => json_encode([
                        'access_token' => 'new_token',
                        'expires_in' => 7200,
                        'refresh_token' => 'new_refresh',
                    ], JSON_THROW_ON_ERROR),
                    'status_code' => 200,
                ];
            }
        };

        self::getContainer()->set(BaiduApiClient::class, $apiClient);

        // Create real entities
        $config = new BaiduOAuth2Config();
        $config->setClientId('cid');
        $config->setClientSecret('sec');

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('uid');
        $user->setAccessToken('old_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setRefreshToken('refresh_old');

        $manager = self::getContainer()->get(BaiduTokenManager::class);
        $this->assertInstanceOf(BaiduTokenManager::class, $manager);
        $data = $manager->refreshToken($user);

        $this->assertEquals('new_token', $data['access_token']);
        $this->assertEquals(7200, $data['expires_in']);
        $this->assertEquals('new_refresh', $data['refresh_token']);

        // Verify user was updated
        $this->assertEquals('new_token', $user->getAccessToken());
        $this->assertEquals(7200, $user->getExpiresIn());
        $this->assertEquals('new_refresh', $user->getRefreshToken());
    }
}
