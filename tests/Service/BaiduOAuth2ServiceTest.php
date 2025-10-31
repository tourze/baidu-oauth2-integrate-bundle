<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2ConfigRepository;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduOAuth2Service;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2Service::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2ServiceTest extends AbstractIntegrationTestCase
{
    private BaiduOAuth2Service $service;

    private BaiduOAuth2ConfigRepository $configRepository;

    protected function onSetUp(): void
    {
        $this->service = self::getService(BaiduOAuth2Service::class);
        $this->configRepository = self::getService(BaiduOAuth2ConfigRepository::class);
    }

    public function testGenerateAuthorizationUrlWithValidConfig(): void
    {
        $config = $this->createValidConfig();
        $this->configRepository->save($config);

        $authUrl = $this->service->generateAuthorizationUrl('test_session_123');

        $this->assertStringContainsString('https://openapi.baidu.com/oauth/2.0/authorize', $authUrl);
        $this->assertStringContainsString('client_id=' . $config->getClientId(), $authUrl);
        $this->assertStringContainsString('response_type=code', $authUrl);
        $this->assertStringContainsString('state=', $authUrl);
        $this->assertStringContainsString('redirect_uri=', $authUrl);
    }

    public function testGenerateAuthorizationUrlWithoutValidConfig(): void
    {
        // 确保没有有效配置
        $configs = $this->configRepository->findAll();
        foreach ($configs as $config) {
            $config->setValid(false);
            $this->configRepository->save($config);
        }

        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('No valid Baidu OAuth2 configuration found');

        $this->service->generateAuthorizationUrl();
    }

    public function testGenerateAuthorizationUrlWithoutSessionId(): void
    {
        $config = $this->createValidConfig();
        $this->configRepository->save($config);

        $authUrl = $this->service->generateAuthorizationUrl();

        $this->assertStringContainsString('https://openapi.baidu.com/oauth/2.0/authorize', $authUrl);
        $this->assertStringContainsString('client_id=' . $config->getClientId(), $authUrl);
    }

    public function testHandleCallbackWithInvalidState(): void
    {
        $this->expectException(BaiduOAuth2Exception::class);

        $this->service->handleCallback('test_code', 'invalid_state');
    }

    public function testHandleCallbackWithExternalApiInteraction(): void
    {
        // 设置有效的配置和状态
        $config = $this->createValidConfig();
        $this->configRepository->save($config);

        // 生成一个有效的授权URL，这会创建一个有效的state
        $authUrl = $this->service->generateAuthorizationUrl('test_session_callback');

        // 从URL中提取state参数
        $parsedUrl = parse_url($authUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $state = $queryParams['state'] ?? '';

        $this->assertIsString($state, 'State should be a string');
        $this->assertNotEmpty($state, 'State should be generated');

        // 由于这是集成测试且涉及外部API调用，我们测试服务的边界行为：
        // 使用无效的授权码来触发可预期的异常
        $this->expectException(BaiduOAuth2Exception::class);

        // 使用无效的代码应该会在token exchange阶段失败
        $this->service->handleCallback('invalid_test_code', $state);
    }

    public function testGetUserInfo(): void
    {
        $baiduUid = 'test_baidu_uid_' . uniqid();

        // 创建一个测试用户
        $config = $this->createValidConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername('Test User');

        $this->persistAndFlush($user);

        $userInfo = $this->service->getUserInfo($baiduUid);

        $this->assertIsArray($userInfo);
        $this->assertNotEmpty($userInfo);
    }

    public function testGetUserInfoWithForceRefresh(): void
    {
        $baiduUid = 'test_baidu_uid_' . uniqid();

        // 创建一个测试用户
        $config = $this->createValidConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername('Test User');

        $this->persistAndFlush($user);

        $userInfo = $this->service->getUserInfo($baiduUid, true);

        $this->assertIsArray($userInfo);
    }

    public function testGetUserInfoWithNonExistentUser(): void
    {
        $this->expectException(BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->getUserInfo('non_existent_uid');
    }

    public function testServiceIsPublic(): void
    {
        $this->assertTrue(self::getContainer()->has(BaiduOAuth2Service::class));
        $service = self::getContainer()->get(BaiduOAuth2Service::class);
        $this->assertInstanceOf(BaiduOAuth2Service::class, $service);
    }

    public function testServiceDependencies(): void
    {
        $service = self::getService(BaiduOAuth2Service::class);

        $this->assertInstanceOf(BaiduOAuth2Service::class, $service);

        // 验证服务的依赖注入正确
        $reflection = new \ReflectionClass($service);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(4, $parameters);

        $parameterNames = array_map(fn ($param) => $param->getName(), $parameters);
        $this->assertContains('configRepository', $parameterNames);
        $this->assertContains('stateManager', $parameterNames);
        $this->assertContains('tokenManager', $parameterNames);
        $this->assertContains('userManager', $parameterNames);
    }

    public function testServiceCanBeInstantiatedFromContainer(): void
    {
        $container = self::getContainer();
        $this->assertTrue($container->has(BaiduOAuth2Service::class));

        $service = $container->get(BaiduOAuth2Service::class);
        $this->assertInstanceOf(BaiduOAuth2Service::class, $service);

        // 验证服务是public的（通过Autoconfigure注解标记）
        $this->assertSame($service, $container->get(BaiduOAuth2Service::class));
    }

    private function createValidConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_' . uniqid());
        $config->setClientSecret('test_secret_' . uniqid());
        $config->setScope('basic');
        $config->setValid(true);

        return $config;
    }
}
