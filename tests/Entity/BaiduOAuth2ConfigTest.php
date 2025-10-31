<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2Config::class)]
final class BaiduOAuth2ConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): BaiduOAuth2Config
    {
        return new BaiduOAuth2Config();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'clientId' => ['clientId', 'test_client_id'];
        yield 'clientSecret' => ['clientSecret', 'test_client_secret'];
        yield 'scope' => ['scope', 'basic email'];
        yield 'valid' => ['valid', false];
    }

    public function testConstruct(): void
    {
        $config = new BaiduOAuth2Config();

        $this->assertNull($config->getCreateTime());
        $this->assertNull($config->getUpdateTime());
        $this->assertTrue($config->isValid());
        $this->assertSame('', $config->getClientId());
        $this->assertSame('', $config->getClientSecret());
        $this->assertNull($config->getScope());
    }

    public function testClientId(): void
    {
        $config = new BaiduOAuth2Config();
        $clientId = 'test_client_id';

        $config->setClientId($clientId);
        $this->assertSame($clientId, $config->getClientId());
    }

    public function testClientSecret(): void
    {
        $config = new BaiduOAuth2Config();
        $clientSecret = 'test_client_secret';

        $config->setClientSecret($clientSecret);
        $this->assertSame($clientSecret, $config->getClientSecret());
    }

    public function testScope(): void
    {
        $config = new BaiduOAuth2Config();
        $scope = 'basic email profile';

        $config->setScope($scope);
        $this->assertSame($scope, $config->getScope());

        $config->setScope(null);
        $this->assertNull($config->getScope());
    }

    public function testIsValid(): void
    {
        $config = new BaiduOAuth2Config();

        $this->assertTrue($config->isValid());

        $config->setValid(false);
        $this->assertFalse($config->isValid());

        $config->setValid(true);
        $this->assertTrue($config->isValid());
    }

    public function testTimestampInitialization(): void
    {
        $config = new BaiduOAuth2Config();

        // TimestampableAware trait 的字段初始时应为null
        $this->assertNull($config->getCreateTime());
        $this->assertNull($config->getUpdateTime());

        // 可以手动设置时间戳
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable();
        $config->setCreateTime($createTime);
        $config->setUpdateTime($updateTime);

        $this->assertSame($createTime, $config->getCreateTime());
        $this->assertSame($updateTime, $config->getUpdateTime());
    }

    public function testToString(): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');

        $string = (string) $config;

        $this->assertStringContainsString('Baidu OAuth2 Config', $string);
        $this->assertStringContainsString('test_client_id', $string);
        $this->assertStringContainsString('#0', $string); // ID为null时显示0
    }

    public function testToStringWithId(): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');

        // 使用反射设置ID
        $reflection = new \ReflectionProperty($config, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($config, 123);

        $string = (string) $config;

        $this->assertStringContainsString('Baidu OAuth2 Config #123', $string);
        $this->assertStringContainsString('test_client_id', $string);
    }
}
