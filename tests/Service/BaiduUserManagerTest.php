<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
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
#[RunTestsInSeparateProcesses]
final class BaiduUserManagerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 基本设置，每个测试中按需创建mock对象
    }

    private function createMockApiClient(): BaiduApiClient
    {
        return new class(self::createMock(HttpClientInterface::class)) extends BaiduApiClient {
            public function __construct(HttpClientInterface $httpClient)
            {
                parent::__construct($httpClient, null);
            }
        };
    }

    public function testMergeUserData(): void
    {
        $api = $this->createMockApiClient();

        // 注入Mock API客户端到服务容器
        self::getContainer()->set(BaiduApiClient::class, $api);

        $manager = self::getService(BaiduUserManager::class);

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
        $api = $this->createMockApiClient();
        self::getContainer()->set(BaiduApiClient::class, $api);

        $manager = self::getService(BaiduUserManager::class);
        $em = self::getService(EntityManagerInterface::class);
        $repo = self::getService(BaiduOAuth2UserRepository::class);

        // 创建测试数据
        $config = new BaiduOAuth2Config();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid');
        $user->setAccessToken('test_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        $em->persist($config);
        $em->persist($user);
        $em->flush();

        $result = $manager->findUserById($user->getId());

        $this->assertSame($user, $result);
    }

    public function testFindUserByIdNotFound(): void
    {
        $api = $this->createMockApiClient();
        self::getContainer()->set(BaiduApiClient::class, $api);

        $manager = self::getService(BaiduUserManager::class);
        $result = $manager->findUserById(999);

        $this->assertNull($result);
    }

    public function testFindUserByIdWithNullId(): void
    {
        $api = $this->createMockApiClient();
        self::getContainer()->set(BaiduApiClient::class, $api);

        $manager = self::getService(BaiduUserManager::class);
        $result = $manager->findUserById(null);

        $this->assertNull($result);
    }

    public function testGetAllUsers(): void
    {
        $api = $this->createMockApiClient();
        self::getContainer()->set(BaiduApiClient::class, $api);

        $manager = self::getService(BaiduUserManager::class);
        $em = self::getService(EntityManagerInterface::class);
        $repo = self::getService(BaiduOAuth2UserRepository::class);

        // 清理可能存在的测试数据
        $existingUsers = $repo->findAll();
        foreach ($existingUsers as $user) {
            if (str_starts_with($user->getBaiduUid(), 'test_uid_getall')) {
                $em->remove($user);
            }
        }
        $em->flush();

        // 创建测试数据
        $config = new BaiduOAuth2Config();
        $config->setClientId('test-client-getall');
        $config->setClientSecret('test-secret-getall');

        $user1 = new BaiduOAuth2User();
        $user1->setBaiduUid('test_uid_getall1');
        $user1->setAccessToken('test_token1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($config);
        $user2 = new BaiduOAuth2User();
        $user2->setBaiduUid('test_uid_getall2');
        $user2->setAccessToken('test_token2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($config);

        $em->persist($config);
        $em->persist($user1);
        $em->persist($user2);
        $em->flush();

        $result = $manager->getAllUsers();

        // 检查我们的测试用户是否在结果中
        $foundUser1 = false;
        $foundUser2 = false;
        foreach ($result as $user) {
            if ($user->getBaiduUid() === 'test_uid_getall1') {
                $foundUser1 = true;
            }
            if ($user->getBaiduUid() === 'test_uid_getall2') {
                $foundUser2 = true;
            }
        }

        $this->assertTrue($foundUser1, 'User with baiduUid test_uid_getall1 should be found');
        $this->assertTrue($foundUser2, 'User with baiduUid test_uid_getall2 should be found');
    }

    public function testFetchUserInfo(): void
    {
        $apiClient = $this->createMock(BaiduApiClient::class);
        $apiClient->method('getDefaultHeaders')->willReturn(['Content-Type' => 'application/json']);
        $apiClient->method('makeRequest')->willReturn([
            'content' => '{"userid":"123","username":"testuser","portrait":"abc123"}',
        ]);

        // 注入Mock依赖到服务容器
        self::getContainer()->set(BaiduApiClient::class, $apiClient);

        $manager = self::getService(BaiduUserManager::class);
        $result = $manager->fetchUserInfo('test-access-token');

        $this->assertIsArray($result);
        $this->assertSame('123', $result['userid']);
        $this->assertSame('testuser', $result['username']);
        $this->assertSame('abc123', $result['portrait']);
    }

    public function testUpdateOrCreateUser(): void
    {
        $apiClient = $this->createMockApiClient();
        self::getContainer()->set(BaiduApiClient::class, $apiClient);

        $manager = self::getService(BaiduUserManager::class);
        $em = self::getService(EntityManagerInterface::class);

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

        $em->persist($config);
        $em->flush();

        $user = $manager->updateOrCreateUser($data, $config);

        $this->assertInstanceOf(BaiduOAuth2User::class, $user);
        $this->assertSame('test-uid', $user->getBaiduUid());
        $this->assertSame('test-token', $user->getAccessToken());
        $this->assertSame('Test User', $user->getUsername());
        $this->assertSame('https://himg.bdimg.com/sys/portrait/item/abc123', $user->getAvatar());
    }
}
