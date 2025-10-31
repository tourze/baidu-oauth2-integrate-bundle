<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2State::class)]
final class BaiduOAuth2StateTest extends AbstractEntityTestCase
{
    private BaiduOAuth2Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new BaiduOAuth2Config();
        $this->config->setClientId('test_client_id');
        $this->config->setClientSecret('test_client_secret');
    }

    protected function createEntity(): BaiduOAuth2State
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state_123');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        return $state;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'sessionId' => ['sessionId', 'test_session_id'];
        yield 'used' => ['used', true];
    }

    public function testConstruct(): void
    {
        $state = 'test_state_value';
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');

        $stateEntity = new BaiduOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);
        $stateEntity->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $this->assertNull($stateEntity->getCreateTime());
        $this->assertNull($stateEntity->getUpdateTime());
        $this->assertSame($state, $stateEntity->getState());
        $this->assertSame($config, $stateEntity->getConfig());
        $this->assertFalse($stateEntity->isUsed());
        $this->assertNull($stateEntity->getSessionId());

        // 验证过期时间设置为10分钟后
        $expectedExpireTime = (new \DateTimeImmutable())->modify('+10 minutes');
        $actualExpireTime = $stateEntity->getExpireTime();
        $this->assertLessThan(5, abs($expectedExpireTime->getTimestamp() - $actualExpireTime->getTimestamp()));
    }

    public function testGetState(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state_value');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $this->assertSame('test_state_value', $state->getState());
    }

    public function testGetExpireTime(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $expireTime = $state->getExpireTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime);
        $this->assertGreaterThan(new \DateTimeImmutable(), $expireTime);
    }

    public function testIsUsed(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $this->assertFalse($state->isUsed());
    }

    public function testSetUsed(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        // 初始状态应该是未使用
        $this->assertFalse($state->isUsed());

        // 设置为已使用
        $state->setUsed(true);
        $this->assertTrue($state->isUsed());

        // 可以重新设置为未使用
        $state->setUsed(false);
        $this->assertFalse($state->isUsed());
    }

    public function testMarkAsUsed(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $this->assertFalse($state->isUsed());

        $state->markAsUsed();
        $this->assertTrue($state->isUsed());
    }

    public function testIsExpired(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        // 新创建的state应该未过期
        $this->assertFalse($state->isExpired());

        // 使用反射设置过期时间为过去
        $reflection = new \ReflectionProperty($state, 'expireTime');
        $reflection->setAccessible(true);
        $reflection->setValue($state, new \DateTimeImmutable('-1 minute'));

        $this->assertTrue($state->isExpired());

        // 设置为未来时间
        $reflection->setValue($state, new \DateTimeImmutable('+1 hour'));
        $this->assertFalse($state->isExpired());
    }

    public function testIsValid(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        // 新创建的state应该有效（未使用且未过期）
        $this->assertTrue($state->isValid());

        // 标记为已使用后应该无效
        $state->markAsUsed();
        $this->assertFalse($state->isValid());

        // 创建新的state并设置为过期
        $state2 = new BaiduOAuth2State();
        $state2->setState('test_state2');
        $state2->setConfig($this->config);
        $state2->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));
        $reflection = new \ReflectionProperty($state2, 'expireTime');
        $reflection->setAccessible(true);
        $reflection->setValue($state2, new \DateTimeImmutable('-1 minute'));

        $this->assertFalse($state2->isValid());
    }

    public function testSessionId(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));
        $sessionId = 'test_session_id_123';

        $this->assertNull($state->getSessionId());

        $state->setSessionId($sessionId);
        $this->assertSame($sessionId, $state->getSessionId());

        $state->setSessionId(null);
        $this->assertNull($state->getSessionId());
    }

    public function testGetConfig(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $this->assertSame($this->config, $state->getConfig());
    }

    public function testTimestampInitialization(): void
    {
        $state = new BaiduOAuth2State();
        $state->setState('test_state');
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        // TimestampableAware trait 的字段初始时应为null
        $this->assertNull($state->getCreateTime());
        $this->assertNull($state->getUpdateTime());

        // 可以手动设置时间戳
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable();
        $state->setCreateTime($createTime);
        $state->setUpdateTime($updateTime);

        $this->assertSame($createTime, $state->getCreateTime());
        $this->assertSame($updateTime, $state->getUpdateTime());
    }

    public function testToString(): void
    {
        $stateValue = 'test_state_value';
        $state = new BaiduOAuth2State();
        $state->setState($stateValue);
        $state->setConfig($this->config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        $string = (string) $state;

        $this->assertStringContainsString('Baidu OAuth2 State', $string);
        $this->assertStringContainsString($stateValue, $string);
    }
}
