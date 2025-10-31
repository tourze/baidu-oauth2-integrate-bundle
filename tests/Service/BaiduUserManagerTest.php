<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2UserRepository;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduApiClient;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduUserManager;

/**
 * @internal
 */
#[CoversClass(BaiduUserManager::class)]
final class BaiduUserManagerTest extends TestCase
{
    private function createMockApiClient(): BaiduApiClient
    {
        return new class(self::createMock(HttpClientInterface::class)) extends BaiduApiClient {
            public function __construct(HttpClientInterface $httpClient)
            {
                parent::__construct($httpClient, null);
            }
        };
    }

    /** @param BaiduOAuth2User[] $returnUsers */
    private function createMockRepository(?BaiduOAuth2User $returnUser = null, array $returnUsers = []): BaiduOAuth2UserRepository
    {
        $mock = $this->createMock(BaiduOAuth2UserRepository::class);
        $mock->method('find')->willReturnCallback(function ($id) use ($returnUser) {
            return 123 === $id ? $returnUser : null;
        });
        $mock->method('findAll')->willReturn($returnUsers);
        $mock->method('findByBaiduUid')->willReturn(null);

        return $mock;
    }

    private function createMockEntityManager(): EntityManagerInterface
    {
        return self::createMock(EntityManagerInterface::class);
    }

    public function testMergeUserData(): void
    {
        $api = $this->createMockApiClient();
        $repo = $this->createMockRepository();
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($api, $repo, $em);

        $tokenData = ['access_token' => 'abc', 'expires_in' => 1234];
        $userInfo = ['userid' => 'u1', 'username' => 'name'];
        $merged = $manager->mergeUserData($tokenData, $userInfo);

        $this->assertEquals('abc', $merged['access_token']);
        $this->assertEquals(1234, $merged['expires_in']);
        $this->assertEquals('u1', $merged['userid']);
        $this->assertEquals('name', $merged['username']);
    }

    public function testFindUserByIdSuccess(): void
    {
        $config = new BaiduOAuth2Config();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid');
        $user->setAccessToken('test_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        $api = $this->createMockApiClient();
        $repo = $this->createMockRepository($user);
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($api, $repo, $em);
        $result = $manager->findUserById(123);

        $this->assertSame($user, $result);
    }

    public function testFindUserByIdNotFound(): void
    {
        $api = $this->createMockApiClient();
        $repo = $this->createMockRepository();
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($api, $repo, $em);
        $result = $manager->findUserById(999);

        $this->assertNull($result);
    }

    public function testFindUserByIdWithNullId(): void
    {
        $api = $this->createMockApiClient();
        $repo = $this->createMockRepository();
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($api, $repo, $em);
        $result = $manager->findUserById(null);

        $this->assertNull($result);
    }

    public function testGetAllUsers(): void
    {
        $config = new BaiduOAuth2Config();
        $user1 = new BaiduOAuth2User();
        $user1->setBaiduUid('test_uid1');
        $user1->setAccessToken('test_token1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($config);
        $user2 = new BaiduOAuth2User();
        $user2->setBaiduUid('test_uid2');
        $user2->setAccessToken('test_token2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($config);
        $users = [$user1, $user2];

        $api = $this->createMockApiClient();
        $repo = $this->createMockRepository(null, $users);
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($api, $repo, $em);
        $result = $manager->getAllUsers();

        $this->assertSame($users, $result);
    }

    public function testFetchUserInfo(): void
    {
        $apiClient = $this->createMock(BaiduApiClient::class);
        $apiClient->method('getDefaultHeaders')->willReturn(['Content-Type' => 'application/json']);
        $apiClient->method('makeRequest')->willReturn([
            'content' => '{"userid":"123","username":"testuser","portrait":"abc123"}',
        ]);

        $repo = $this->createMockRepository();
        $em = $this->createMockEntityManager();

        $manager = new BaiduUserManager($apiClient, $repo, $em);
        $result = $manager->fetchUserInfo('test-access-token');

        $this->assertIsArray($result);
        $this->assertSame('123', $result['userid']);
        $this->assertSame('testuser', $result['username']);
        $this->assertSame('abc123', $result['portrait']);
    }

    public function testUpdateOrCreateUser(): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test-client');
        $config->setClientSecret('test-secret');

        $data = [
            'userid' => 'test-uid',
            'access_token' => 'test-token',
            'expires_in' => 3600,
            'username' => 'Test User',
            'portrait' => 'abc123',
        ];

        $repo = $this->createMock(BaiduOAuth2UserRepository::class);
        $repo->method('findByBaiduUid')->with('test-uid')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $apiClient = $this->createMockApiClient();

        $manager = new BaiduUserManager($apiClient, $repo, $em);
        $user = $manager->updateOrCreateUser($data, $config);

        $this->assertInstanceOf(BaiduOAuth2User::class, $user);
        $this->assertSame('test-uid', $user->getBaiduUid());
        $this->assertSame('test-token', $user->getAccessToken());
        $this->assertSame('Test User', $user->getUsername());
        $this->assertSame('https://himg.bdimg.com/sys/portrait/item/abc123', $user->getAvatar());
    }
}
