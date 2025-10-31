<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2StateRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2StateRepository::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2StateRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // AbstractRepositoryTestCase 会自动处理设置
    }

    public function testFindValidStateWithValidState(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $state = $this->createValidState('test_state_' . uniqid(), $config);
        $repository->save($state);

        $foundState = $repository->findValidState($state->getState());
        $this->assertNotNull($foundState);
        $this->assertEquals($state->getState(), $foundState->getState());
        $this->assertTrue($foundState->isValid());
    }

    public function testFindValidStateWithExpiredState(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $state = $this->createExpiredState('expired_state_' . uniqid(), $config);
        $repository->save($state);

        $foundState = $repository->findValidState($state->getState());
        $this->assertNull($foundState);
    }

    public function testFindValidStateWithUsedState(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $state = $this->createValidState('used_state_' . uniqid(), $config);
        $state->markAsUsed();
        $repository->save($state);

        $foundState = $repository->findValidState($state->getState());
        $this->assertNull($foundState);
    }

    public function testFindValidStateWithNonExistentState(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $foundState = $repository->findValidState('non_existent_state');
        $this->assertNull($foundState);
    }

    public function testCleanupExpiredStatesRemovesOnlyExpiredStates(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();

        // 清理已有的过期数据
        $repository->cleanupExpiredStates();

        // 创建有效状态
        $validState = $this->createValidState('valid_state_' . uniqid(), $config);
        $repository->save($validState);

        // 创建过期状态
        $expiredState1 = $this->createExpiredState('expired_state_1_' . uniqid(), $config);
        $expiredState2 = $this->createExpiredState('expired_state_2_' . uniqid(), $config);
        $repository->save($expiredState1);
        $repository->save($expiredState2);

        // 清理过期状态
        $removedCount = $repository->cleanupExpiredStates();
        $this->assertEquals(2, $removedCount);

        // 清空实体管理器缓存以确保从数据库重新查询
        self::getEntityManager()->clear();

        // 验证有效状态仍然存在
        $foundValidState = $repository->find($validState->getId());
        $this->assertNotNull($foundValidState);

        // 验证过期状态已被删除
        $foundExpiredState1 = $repository->find($expiredState1->getId());
        $foundExpiredState2 = $repository->find($expiredState2->getId());
        $this->assertNull($foundExpiredState1);
        $this->assertNull($foundExpiredState2);
    }

    public function testCleanupExpiredStatesReturnsZeroWhenNoExpiredStates(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        // 清理现有的过期状态
        $repository->cleanupExpiredStates();

        $config = $this->createConfig();
        $validState = $this->createValidState('valid_state_' . uniqid(), $config);
        $repository->save($validState);

        // 再次清理应该返回0
        $removedCount = $repository->cleanupExpiredStates();
        $this->assertEquals(0, $removedCount);

        // 验证有效状态仍然存在
        $foundState = $repository->find($validState->getId());
        $this->assertNotNull($foundState);
    }

    public function testSaveAndRemoveWithFlushTrue(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $state = $this->createValidState('test_state_' . uniqid(), $config);

        // 保存
        $repository->save($state, true);
        $this->assertNotNull($state->getId());

        $foundState = $repository->find($state->getId());
        $this->assertNotNull($foundState);

        // 删除
        $stateId = $state->getId();
        $repository->remove($state, true);
        $foundState = $repository->find($stateId);
        $this->assertNull($foundState);
    }

    public function testSaveAndRemoveWithFlushFalse(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $state = $this->createValidState('test_state_' . uniqid(), $config);

        // 保存但不立即flush
        $repository->save($state, false);
        self::getEntityManager()->flush();
        $this->assertNotNull($state->getId());

        $foundState = $repository->find($state->getId());
        $this->assertNotNull($foundState);

        // 删除但不立即flush
        $repository->remove($state, false);
        $foundState = $repository->find($state->getId());
        $this->assertNotNull($foundState); // 仍然存在，因为还没flush

        // 手动flush
        $stateId = $state->getId();
        self::getEntityManager()->flush();
        $foundState = $repository->find($stateId);
        $this->assertNull($foundState); // 现在应该被删除了
    }

    public function testFindBySessionIdFilter(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config = $this->createConfig();
        $sessionId = 'test_session_' . uniqid();

        $state1 = $this->createValidState('state_1_' . uniqid(), $config);
        $state1->setSessionId($sessionId);

        $state2 = $this->createValidState('state_2_' . uniqid(), $config);
        $state2->setSessionId('other_session');

        $repository->save($state1);
        $repository->save($state2);

        // 按sessionId查找
        $states = $repository->findBy(['sessionId' => $sessionId]);
        $this->assertCount(1, $states);
        $this->assertEquals($state1->getState(), $states[0]->getState());

        // 查找null sessionId的状态
        $state3 = $this->createValidState('state_3_' . uniqid(), $config);
        $repository->save($state3);

        $statesWithNullSession = $repository->findBy(['sessionId' => null]);
        $this->assertGreaterThanOrEqual(1, count($statesWithNullSession));
    }

    public function testFindByConfigFilter(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2StateRepository::class, $repository);

        $config1 = $this->createConfig();
        $config2 = $this->createConfig();

        $state1 = $this->createValidState('state_1_' . uniqid(), $config1);
        $state2 = $this->createValidState('state_2_' . uniqid(), $config2);

        $repository->save($state1);
        $repository->save($state2);

        // 按config查找
        $statesForConfig1 = $repository->findBy(['config' => $config1]);
        $this->assertGreaterThanOrEqual(1, count($statesForConfig1));

        $found = false;
        foreach ($statesForConfig1 as $state) {
            if ($state->getState() === $state1->getState()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    protected function createNewEntity(): object
    {
        $config = $this->createConfig();

        $state = new BaiduOAuth2State();
        $state->setState('test_state_' . uniqid());
        $state->setConfig($config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        return $state;
    }

    /**
     * @return ServiceEntityRepository<BaiduOAuth2State>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(BaiduOAuth2StateRepository::class);
    }

    private function createConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $config->setScope('basic');

        return $config;
    }

    private function createValidState(string $stateValue, BaiduOAuth2Config $config): BaiduOAuth2State
    {
        $state = new BaiduOAuth2State();
        $state->setState($stateValue);
        $state->setConfig($config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        return $state;
    }

    private function createExpiredState(string $stateValue, BaiduOAuth2Config $config): BaiduOAuth2State
    {
        $state = new BaiduOAuth2State();
        $state->setState($stateValue);
        $state->setConfig($config);
        // 设置过期时间为2小时前，确保明确过期
        $state->setExpireTime((new \DateTimeImmutable())->modify('-2 hours'));

        return $state;
    }
}
