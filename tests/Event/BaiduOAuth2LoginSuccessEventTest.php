<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Event\BaiduOAuth2LoginSuccessEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2LoginSuccessEvent::class)]
final class BaiduOAuth2LoginSuccessEventTest extends AbstractEventTestCase
{
    public function testEventConstructionWithUser(): void
    {
        $user = $this->createTestUser();
        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $this->assertSame($user, $event->getUser());
    }

    public function testGetUserReturnsCorrectUser(): void
    {
        $user = $this->createTestUser();
        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $result = $event->getUser();

        $this->assertInstanceOf(BaiduOAuth2User::class, $result);
        $this->assertSame($user, $result);
        $this->assertEquals($user->getBaiduUid(), $result->getBaiduUid());
        $this->assertEquals($user->getUsername(), $result->getUsername());
    }

    public function testEventWithUserHavingAllProperties(): void
    {
        $config = $this->createTestConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_baidu_uid_123');
        $user->setAccessToken('access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername('Test User');
        $user->setAvatar('https://example.com/avatar.jpg');
        $user->setRefreshToken('refresh_token_123');
        $user->setRawData(['key' => 'value']);

        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $retrievedUser = $event->getUser();
        $this->assertEquals('test_baidu_uid_123', $retrievedUser->getBaiduUid());
        $this->assertEquals('Test User', $retrievedUser->getUsername());
        $this->assertEquals('https://example.com/avatar.jpg', $retrievedUser->getAvatar());
        $this->assertEquals('refresh_token_123', $retrievedUser->getRefreshToken());
        $this->assertNotNull($retrievedUser->getRawData());
    }

    public function testEventWithMinimalUserData(): void
    {
        $config = $this->createTestConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('minimal_uid_456');
        $user->setAccessToken('minimal_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $retrievedUser = $event->getUser();
        $this->assertEquals('minimal_uid_456', $retrievedUser->getBaiduUid());
        $this->assertNull($retrievedUser->getUsername());
        $this->assertNull($retrievedUser->getAvatar());
        $this->assertNull($retrievedUser->getRefreshToken());
    }

    public function testEventUserImmutability(): void
    {
        $user = $this->createTestUser();
        $originalUsername = $user->getUsername();

        $event = new BaiduOAuth2LoginSuccessEvent($user);

        // Modify the original user object
        $user->setUsername('Modified Username');

        // The event should still reference the same user object (by design)
        // but the change will be reflected since it's the same object reference
        $retrievedUser = $event->getUser();
        $this->assertSame($user, $retrievedUser);
        $this->assertEquals('Modified Username', $retrievedUser->getUsername());
    }

    public function testEventWithUserHavingSpecialCharacters(): void
    {
        $config = $this->createTestConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('uid_特殊字符_123');
        $user->setAccessToken('special_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername('用户名_éñ_中文');

        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $retrievedUser = $event->getUser();
        $this->assertEquals('uid_特殊字符_123', $retrievedUser->getBaiduUid());
        $this->assertEquals('用户名_éñ_中文', $retrievedUser->getUsername());
    }

    public function testEventWithUserHavingLongStrings(): void
    {
        $longString = str_repeat('a', 255); // 限制长度以符合数据库约束
        $config = $this->createTestConfig();

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('long_test_uid');
        $user->setAccessToken('long_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername($longString);

        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $retrievedUser = $event->getUser();
        $this->assertEquals('long_test_uid', $retrievedUser->getBaiduUid());
        $this->assertEquals($longString, $retrievedUser->getUsername());
    }

    public function testEventConstructorAcceptsReadonlyUser(): void
    {
        $user = $this->createTestUser();

        // The constructor parameter is marked as readonly
        $event = new BaiduOAuth2LoginSuccessEvent($user);

        $this->assertInstanceOf(BaiduOAuth2LoginSuccessEvent::class, $event);
        $this->assertSame($user, $event->getUser());
    }

    public function testMultipleEventsWithSameUser(): void
    {
        $user = $this->createTestUser();

        $event1 = new BaiduOAuth2LoginSuccessEvent($user);
        $event2 = new BaiduOAuth2LoginSuccessEvent($user);

        $this->assertNotSame($event1, $event2);
        $this->assertSame($user, $event1->getUser());
        $this->assertSame($user, $event2->getUser());
        $this->assertSame($event1->getUser(), $event2->getUser());
    }

    public function testEventWithDifferentUsers(): void
    {
        $config = $this->createTestConfig();
        $user1 = new BaiduOAuth2User();
        $user1->setBaiduUid('user_1');
        $user1->setAccessToken('token_1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($config);
        $user1->setUsername('User One');

        $user2 = new BaiduOAuth2User();
        $user2->setBaiduUid('user_2');
        $user2->setAccessToken('token_2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($config);
        $user2->setUsername('User Two');

        $event1 = new BaiduOAuth2LoginSuccessEvent($user1);
        $event2 = new BaiduOAuth2LoginSuccessEvent($user2);

        $this->assertNotSame($event1->getUser(), $event2->getUser());
        $this->assertEquals('user_1', $event1->getUser()->getBaiduUid());
        $this->assertEquals('user_2', $event2->getUser()->getBaiduUid());
        $this->assertEquals('User One', $event1->getUser()->getUsername());
        $this->assertEquals('User Two', $event2->getUser()->getUsername());
    }

    public function testEventWithTokenExpirationData(): void
    {
        $config = $this->createTestConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('expiration_test_uid');
        $user->setAccessToken('expiration_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);

        $event = new BaiduOAuth2LoginSuccessEvent($user);
        $retrievedUser = $event->getUser();

        $this->assertEquals(7200, $retrievedUser->getExpiresIn());
        $this->assertFalse($retrievedUser->isTokenExpired());
        $this->assertInstanceOf(\DateTimeImmutable::class, $retrievedUser->getExpireTime());
    }

    private function createTestUser(): BaiduOAuth2User
    {
        $config = $this->createTestConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_baidu_uid_' . uniqid());
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $user->setUsername('Test User');
        $user->setAvatar('https://example.com/avatar.jpg');

        return $user;
    }

    private function createTestConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_' . uniqid());
        $config->setClientSecret('test_secret_' . uniqid());
        $config->setScope('basic');
        $config->setValid(true);

        return $config;
    }
}
