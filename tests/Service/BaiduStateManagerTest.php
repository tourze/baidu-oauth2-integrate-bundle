<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2StateRepository;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduStateManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduStateManager::class)]
#[RunTestsInSeparateProcesses]
final class BaiduStateManagerTest extends AbstractIntegrationTestCase
{
    private BaiduStateManager $manager;
    private BaiduOAuth2StateRepository $stateRepository;

    protected function onSetUp(): void
    {
        $this->manager = self::getService(BaiduStateManager::class);
        $this->stateRepository = self::getService(BaiduOAuth2StateRepository::class);
    }

    public function testGenerateAuthorizationUrl(): void
    {
        $config = $this->createConfig();
        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        $url = $this->manager->generateAuthorizationUrl($config, 'test_session');

        $this->assertStringContainsString('https://openapi.baidu.com/oauth/2.0/authorize?', $url);
        $this->assertStringContainsString('client_id=test_cid', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);

        // 验证 state 被持久化
        $states = $this->stateRepository->findBy(['config' => $config]);
        $this->assertNotEmpty($states);
    }

    public function testCleanupExpiredStates(): void
    {
        $config = $this->createConfig();
        self::getEntityManager()->persist($config);

        // 创建过期的 state
        $expiredState = new BaiduOAuth2State();
        $expiredState->setState('expired_state_' . uniqid());
        $expiredState->setConfig($config);
        $expiredState->setExpireTime((new \DateTimeImmutable())->modify('-1 hour'));
        self::getEntityManager()->persist($expiredState);

        // 创建未过期的 state
        $validState = new BaiduOAuth2State();
        $validState->setState('valid_state_' . uniqid());
        $validState->setConfig($config);
        $validState->setExpireTime((new \DateTimeImmutable())->modify('+1 hour'));
        self::getEntityManager()->persist($validState);

        self::getEntityManager()->flush();

        $deletedCount = $this->manager->cleanupExpiredStates();

        // 验证过期的 state 被删除
        $this->assertGreaterThanOrEqual(1, $deletedCount);
    }

    public function testGenerateRedirectUri(): void
    {
        $uri = $this->manager->generateRedirectUri();

        $this->assertNotEmpty($uri);
        $this->assertStringContainsString('/baidu-oauth2/callback', $uri);
    }

    public function testValidateAndMarkStateAsUsed(): void
    {
        $config = $this->createConfig();
        self::getEntityManager()->persist($config);

        $stateValue = 'test_state_' . uniqid();
        $state = new BaiduOAuth2State();
        $state->setState($stateValue);
        $state->setConfig($config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        self::getEntityManager()->persist($state);
        self::getEntityManager()->flush();

        $result = $this->manager->validateAndMarkStateAsUsed($stateValue);

        $this->assertNotNull($result);
        $this->assertSame($stateValue, $result->getState());
        $this->assertTrue($result->isUsed());
    }

    public function testValidateAndMarkStateAsUsedThrowsExceptionForInvalidState(): void
    {
        $this->expectException(\Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->manager->validateAndMarkStateAsUsed('non_existent_state');
    }

    private function createConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_cid');
        $config->setClientSecret('test_secret');
        $config->setScope('basic');

        return $config;
    }
}
