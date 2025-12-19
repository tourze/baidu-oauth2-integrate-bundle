<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2ConfigRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2ConfigRepository::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2ConfigRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // AbstractRepositoryTestCase 会自动处理设置
    }

    public function testFindValidConfigReturnsActiveConfig(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        // 创建一个有效配置
        $config = $this->createValidConfig('valid_client_' . uniqid(), 'valid_secret_' . uniqid());
        $repository->save($config);

        $foundConfig = $repository->findValidConfig();
        $this->assertNotNull($foundConfig);
        $this->assertEquals($config->getClientId(), $foundConfig->getClientId());
        $this->assertTrue($foundConfig->isValid());
    }

    public function testFindValidConfigReturnsNullWhenNoValidConfig(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        // 删除所有现有的有效配置
        $existingConfigs = $repository->findBy(['valid' => true]);
        foreach ($existingConfigs as $existingConfig) {
            $repository->remove($existingConfig, false);
        }
        self::getEntityManager()->flush();

        // 创建一个无效配置
        $config = $this->createInvalidConfig('invalid_client_' . uniqid(), 'invalid_secret_' . uniqid());
        $repository->save($config);

        $foundConfig = $repository->findValidConfig();
        $this->assertNull($foundConfig);
    }

    public function testFindValidConfigReturnsLatestValidConfig(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        // 创建多个有效配置
        $config1 = $this->createValidConfig('client_1_' . uniqid(), 'secret_1_' . uniqid());
        $config2 = $this->createValidConfig('client_2_' . uniqid(), 'secret_2_' . uniqid());

        $repository->save($config1);
        // 短暂延迟以确保不同的创建时间
        usleep(1000);
        $repository->save($config2);

        $foundConfig = $repository->findValidConfig();
        $this->assertNotNull($foundConfig);
        // 应该返回最新的（ID更大的）配置
        $this->assertEquals($config2->getClientId(), $foundConfig->getClientId());
    }

    public function testFindByClientIdReturnsMatchingConfig(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $clientId = 'test_client_' . uniqid();
        $config = $this->createValidConfig($clientId, 'test_secret_' . uniqid());
        $repository->save($config);

        $foundConfig = $repository->findByClientId($clientId);
        $this->assertNotNull($foundConfig);
        $this->assertEquals($clientId, $foundConfig->getClientId());
    }

    public function testFindByClientIdReturnsNullForNonExistentClientId(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $foundConfig = $repository->findByClientId('non_existent_client_id');
        $this->assertNull($foundConfig);
    }

    public function testSaveWithFlushTrue(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $config = $this->createValidConfig('test_client_' . uniqid(), 'test_secret_' . uniqid());

        $repository->save($config, true);
        $this->assertNotNull($config->getId());

        $foundConfig = $repository->find($config->getId());
        $this->assertNotNull($foundConfig);
        $this->assertEquals($config->getClientId(), $foundConfig->getClientId());
    }

    public function testSaveWithFlushFalse(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $config = $this->createValidConfig('test_client_' . uniqid(), 'test_secret_' . uniqid());

        $repository->save($config, false);
        self::getEntityManager()->flush();
        $this->assertNotNull($config->getId());

        $foundConfig = $repository->find($config->getId());
        $this->assertNotNull($foundConfig);
        $this->assertEquals($config->getClientId(), $foundConfig->getClientId());
    }

    public function testRemoveWithFlushTrue(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $config = $this->createValidConfig('test_client_' . uniqid(), 'test_secret_' . uniqid());
        $repository->save($config, true);
        $configId = $config->getId();

        $repository->remove($config, true);

        $foundConfig = $repository->find($configId);
        $this->assertNull($foundConfig);
    }

    public function testRemoveWithFlushFalse(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $config = $this->createValidConfig('test_client_' . uniqid(), 'test_secret_' . uniqid());
        $repository->save($config, true);
        $configId = $config->getId();

        $repository->remove($config, false);

        // 删除前应该仍然存在
        $foundConfig = $repository->find($configId);
        $this->assertNotNull($foundConfig);

        // 手动flush后应该被删除
        self::getEntityManager()->flush();
        $foundConfig = $repository->find($configId);
        $this->assertNull($foundConfig);
    }

    public function testClearCacheMethodExists(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        // 验证方法调用成功，没有异常抛出
        $repository->clearCache();

        // 验证调用成功（没有抛出异常）
        $this->assertTrue(true);
    }

    public function testFindValidConfigWithCacheHit(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $config = $this->createValidConfig('cached_client_' . uniqid(), 'cached_secret_' . uniqid());
        $repository->save($config);

        // 第一次调用会缓存结果
        $result1 = $repository->findValidConfig();
        $result2 = $repository->findValidConfig();

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        $this->assertEquals($result1->getClientId(), $result2->getClientId());
    }

    public function testFindByClientIdWithCacheHit(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2ConfigRepository::class, $repository);

        $clientId = 'cached_client_' . uniqid();
        $config = $this->createValidConfig($clientId, 'cached_secret_' . uniqid());
        $repository->save($config);

        // 第一次调用会缓存结果
        $result1 = $repository->findByClientId($clientId);
        $result2 = $repository->findByClientId($clientId);

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        $this->assertEquals($clientId, $result1->getClientId());
        $this->assertEquals($clientId, $result2->getClientId());
    }

    protected function createNewEntity(): object
    {
        return $this->createValidConfig('test_client_' . uniqid(), 'test_secret_' . uniqid());
    }

    /**
     * @return ServiceEntityRepository<BaiduOAuth2Config>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(BaiduOAuth2ConfigRepository::class);
    }

    private function createValidConfig(string $clientId, string $clientSecret): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setScope('basic');
        $config->setValid(true);

        return $config;
    }

    private function createInvalidConfig(string $clientId, string $clientSecret): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setScope('basic');
        $config->setValid(false);

        return $config;
    }
}
