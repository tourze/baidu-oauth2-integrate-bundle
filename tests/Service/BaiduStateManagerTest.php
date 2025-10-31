<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2StateRepository;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduStateManager;

/**
 * @internal
 */
#[CoversClass(BaiduStateManager::class)]
final class BaiduStateManagerTest extends TestCase
{
    public function testGenerateAuthorizationUrl(): void
    {
        $repo = $this->createMockRepository();
        $counter = $this->createCallCounter();
        $em = $this->createMockEntityManager($counter);
        $urlGen = $this->createMockUrlGenerator();

        $manager = new BaiduStateManager($repo, $em, $urlGen);
        $config = new BaiduOAuth2Config();
        $config->setClientId('cid');
        $config->setClientSecret('sec');
        $config->setScope('basic');

        $url = $manager->generateAuthorizationUrl($config, 'session');

        $this->assertStringContainsString('https://openapi.baidu.com/oauth/2.0/authorize?', $url);
        $this->assertStringContainsString('client_id=cid', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);

        // Verify that persist and flush were called once
        // @phpstan-ignore-next-line
        $this->assertSame(1, $counter->getPersistCallCount());
        // @phpstan-ignore-next-line
        $this->assertSame(1, $counter->getFlushCallCount());
    }

    private function createMockRepository(): BaiduOAuth2StateRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);

        return new class($registry) extends BaiduOAuth2StateRepository {
            public function __construct(ManagerRegistry $registry)
            {
                parent::__construct($registry);
            }
        };
    }

    private function createCallCounter(): object
    {
        return new class {
            public int $persistCallCount = 0;

            public int $flushCallCount = 0;

            public function incrementPersistCount(): void
            {
                ++$this->persistCallCount;
            }

            public function incrementFlushCount(): void
            {
                ++$this->flushCallCount;
            }

            public function getPersistCallCount(): int
            {
                return $this->persistCallCount;
            }

            public function getFlushCallCount(): int
            {
                return $this->flushCallCount;
            }
        };
    }

    private function createMockEntityManager(object $counter): EntityManagerInterface
    {
        $mock = $this->createMock(EntityManagerInterface::class);
        $mock->method('persist')->willReturnCallback(function () use ($counter): void {
            // @phpstan-ignore-next-line
            $counter->incrementPersistCount();
        });
        $mock->method('flush')->willReturnCallback(function () use ($counter): void {
            // @phpstan-ignore-next-line
            $counter->incrementFlushCount();
        });

        return $mock;
    }

    private function createMockUrlGenerator(): UrlGeneratorInterface
    {
        $mock = $this->createMock(UrlGeneratorInterface::class);
        $mock->method('generate')->willReturn('http://localhost/baidu-oauth2/callback');

        return $mock;
    }

    public function testCleanupExpiredStates(): void
    {
        $repo = $this->createMock(BaiduOAuth2StateRepository::class);
        $repo->method('cleanupExpiredStates')->willReturn(5);

        $em = $this->createMock(EntityManagerInterface::class);
        $urlGen = $this->createMockUrlGenerator();

        $manager = new BaiduStateManager($repo, $em, $urlGen);
        $result = $manager->cleanupExpiredStates();

        $this->assertSame(5, $result);
    }

    public function testGenerateRedirectUri(): void
    {
        $repo = $this->createMock(BaiduOAuth2StateRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $urlGen = $this->createMockUrlGenerator();

        $manager = new BaiduStateManager($repo, $em, $urlGen);
        $uri = $manager->generateRedirectUri();

        $this->assertSame('http://localhost/baidu-oauth2/callback', $uri);
    }

    public function testValidateAndMarkStateAsUsed(): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test-client');
        $config->setClientSecret('test-secret');

        $state = new BaiduOAuth2State();
        $state->setState('test-state');
        $state->setConfig($config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $repo = $this->createMock(BaiduOAuth2StateRepository::class);
        $repo->method('findValidState')->with('test-state')->willReturn($state);

        $counter = $this->createCallCounter();
        $em = $this->createMockEntityManager($counter);
        $urlGen = $this->createMockUrlGenerator();

        $manager = new BaiduStateManager($repo, $em, $urlGen);
        $result = $manager->validateAndMarkStateAsUsed('test-state');

        $this->assertSame($state, $result);
        $this->assertTrue($result->isUsed());
        // @phpstan-ignore-next-line
        $this->assertSame(1, $counter->getPersistCallCount());
        // @phpstan-ignore-next-line
        $this->assertSame(1, $counter->getFlushCallCount());
    }
}
