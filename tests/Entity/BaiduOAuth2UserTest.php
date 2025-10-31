<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2User::class)]
final class BaiduOAuth2UserTest extends AbstractEntityTestCase
{
    private BaiduOAuth2Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new BaiduOAuth2Config();
        $this->config->setClientId('test_client_id');
        $this->config->setClientSecret('test_client_secret');
    }

    protected function createEntity(): BaiduOAuth2User
    {
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_baidu_uid');
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);

        return $user;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'baiduUid' => ['baiduUid', 'updated_baidu_uid'];
        yield 'accessToken' => ['accessToken', 'updated_access_token'];
        yield 'expiresIn' => ['expiresIn', 7200];
        yield 'refreshToken' => ['refreshToken', 'test_refresh_token'];
        yield 'username' => ['username', 'Test User'];
        yield 'avatar' => ['avatar', 'https://example.com/avatar.jpg'];
        yield 'rawData' => ['rawData', ['key' => 'value', 'user_id' => '12345']];
    }

    public function testConstruct(): void
    {
        $baiduUid = 'test_baidu_uid';
        $accessToken = 'test_access_token';
        $expiresIn = 3600;
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');

        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken($accessToken);
        $user->setExpiresIn($expiresIn);
        $user->setConfig($config);

        $this->assertNull($user->getCreateTime());
        $this->assertNull($user->getUpdateTime());
        $this->assertSame($baiduUid, $user->getBaiduUid());
        $this->assertSame($accessToken, $user->getAccessToken());
        $this->assertSame($expiresIn, $user->getExpiresIn());
        $this->assertSame($config, $user->getConfig());
        $this->assertNull($user->getRefreshToken());
        $this->assertNull($user->getUsername());
        $this->assertNull($user->getAvatar());
        $this->assertNull($user->getRawData());

        // 验证过期时间设置正确
        $expectedExpireTime = (new \DateTimeImmutable())->modify("+{$expiresIn} seconds");
        $actualExpireTime = $user->getExpireTime();
        $this->assertLessThan(5, abs($expectedExpireTime->getTimestamp() - $actualExpireTime->getTimestamp()));
    }

    public function testBaiduUid(): void
    {
        $user = $this->createEntity();
        $newUid = 'new_baidu_uid';

        $this->assertSame('test_baidu_uid', $user->getBaiduUid());

        $user->setBaiduUid($newUid);
        $this->assertSame($newUid, $user->getBaiduUid());
    }

    public function testAccessToken(): void
    {
        $user = $this->createEntity();
        $newToken = 'new_access_token';

        $this->assertSame('test_access_token', $user->getAccessToken());

        $user->setAccessToken($newToken);
        $this->assertSame($newToken, $user->getAccessToken());
    }

    public function testExpiresIn(): void
    {
        $user = $this->createEntity();
        $newExpiresIn = 7200;
        $oldExpireTime = $user->getExpireTime();

        $this->assertSame(3600, $user->getExpiresIn());

        $user->setExpiresIn($newExpiresIn);
        $this->assertSame($newExpiresIn, $user->getExpiresIn());

        // 验证过期时间也被更新
        $newExpireTime = $user->getExpireTime();
        $this->assertNotSame($oldExpireTime, $newExpireTime);
        $expectedExpireTime = (new \DateTimeImmutable())->modify("+{$newExpiresIn} seconds");
        $this->assertLessThan(5, abs($expectedExpireTime->getTimestamp() - $newExpireTime->getTimestamp()));
    }

    public function testExpireTime(): void
    {
        $user = $this->createEntity();
        $newExpireTime = new \DateTimeImmutable('+2 hours');

        $user->setExpireTime($newExpireTime);
        $this->assertSame($newExpireTime, $user->getExpireTime());

        // 测试从DateTime转换为DateTimeImmutable
        $dateTime = new \DateTime('+3 hours');
        $user->setExpireTime($dateTime);

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getExpireTime());
        $this->assertEquals($dateTime->getTimestamp(), $user->getExpireTime()->getTimestamp());
    }

    public function testIsTokenExpired(): void
    {
        $user = $this->createEntity();

        // 新创建的用户token应该未过期
        $this->assertFalse($user->isTokenExpired());

        // 设置过期时间为过去
        $user->setExpireTime(new \DateTimeImmutable('-1 minute'));
        $this->assertTrue($user->isTokenExpired());

        // 设置过期时间为未来
        $user->setExpireTime(new \DateTimeImmutable('+1 hour'));
        $this->assertFalse($user->isTokenExpired());

        // 测试边界情况：当前时间
        $user->setExpireTime(new \DateTimeImmutable());
        $this->assertTrue($user->isTokenExpired()); // <= 比较，所以相等时也算过期
    }

    public function testRefreshToken(): void
    {
        $user = $this->createEntity();
        $refreshToken = 'test_refresh_token';

        $this->assertNull($user->getRefreshToken());

        $user->setRefreshToken($refreshToken);
        $this->assertSame($refreshToken, $user->getRefreshToken());

        $user->setRefreshToken(null);
        $this->assertNull($user->getRefreshToken());
    }

    public function testUsername(): void
    {
        $user = $this->createEntity();
        $username = 'Test User Name';

        $this->assertNull($user->getUsername());

        $user->setUsername($username);
        $this->assertSame($username, $user->getUsername());

        $user->setUsername(null);
        $this->assertNull($user->getUsername());
    }

    public function testAvatar(): void
    {
        $user = $this->createEntity();
        $avatar = 'https://example.com/avatar.jpg';

        $this->assertNull($user->getAvatar());

        $user->setAvatar($avatar);
        $this->assertSame($avatar, $user->getAvatar());

        $user->setAvatar(null);
        $this->assertNull($user->getAvatar());
    }

    public function testRawData(): void
    {
        $user = $this->createEntity();
        $rawData = [
            'userid' => '12345',
            'username' => 'testuser',
            'portrait' => 'avatar_url',
        ];

        $this->assertNull($user->getRawData());

        $user->setRawData($rawData);
        $this->assertSame($rawData, $user->getRawData());

        $user->setRawData(null);
        $this->assertNull($user->getRawData());
    }

    public function testGetConfig(): void
    {
        $user = $this->createEntity();

        $this->assertSame($this->config, $user->getConfig());
    }

    public function testTimestampInitialization(): void
    {
        $user = $this->createEntity();

        // TimestampableAware trait 的字段初始时应为null
        $this->assertNull($user->getCreateTime());
        $this->assertNull($user->getUpdateTime());

        // 可以手动设置时间戳
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable();
        $user->setCreateTime($createTime);
        $user->setUpdateTime($updateTime);

        $this->assertSame($createTime, $user->getCreateTime());
        $this->assertSame($updateTime, $user->getUpdateTime());
    }

    public function testToString(): void
    {
        $baiduUid = 'test_baidu_uid_123';
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken('token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);

        $string = (string) $user;

        $this->assertStringContainsString('Baidu OAuth2 User', $string);
        $this->assertStringContainsString($baiduUid, $string);
    }
}
