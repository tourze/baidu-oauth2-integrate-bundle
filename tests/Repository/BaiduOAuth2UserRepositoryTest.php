<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2UserRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2UserRepository::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2UserRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // AbstractRepositoryTestCase 会自动处理设置
    }

    public function testFindByBaiduUidWithExistingUser(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $baiduUid = 'test_baidu_uid_' . uniqid();
        $user = $this->createBaiduUser($baiduUid, $config);
        $repository->save($user);

        $foundUser = $repository->findByBaiduUid($baiduUid);
        $this->assertNotNull($foundUser);
        $this->assertEquals($baiduUid, $foundUser->getBaiduUid());
        $this->assertEquals($user->getAccessToken(), $foundUser->getAccessToken());
    }

    public function testFindByBaiduUidWithNonExistingUser(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $foundUser = $repository->findByBaiduUid('non_existing_uid');
        $this->assertNull($foundUser);
    }

    public function testFindExpiredTokenUsersReturnsExpiredUsers(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();

        // 清理现有的过期用户
        $existingExpiredUsers = $repository->findExpiredTokenUsers();
        foreach ($existingExpiredUsers as $user) {
            $repository->remove($user);
        }

        // 创建有效用户
        $validUser = $this->createBaiduUser('valid_uid_' . uniqid(), $config);
        $repository->save($validUser);

        // 创建过期用户
        $expiredUser1 = $this->createExpiredUser('expired_uid_1_' . uniqid(), $config);
        $expiredUser2 = $this->createExpiredUser('expired_uid_2_' . uniqid(), $config);
        $repository->save($expiredUser1);
        $repository->save($expiredUser2);

        // 查找过期用户
        $expiredUsers = $repository->findExpiredTokenUsers();
        $this->assertCount(2, $expiredUsers);

        $expiredUids = array_map(fn ($user) => $user->getBaiduUid(), $expiredUsers);
        $this->assertContains($expiredUser1->getBaiduUid(), $expiredUids);
        $this->assertContains($expiredUser2->getBaiduUid(), $expiredUids);

        // 验证有效用户不在结果中
        $validUserInExpired = false;
        foreach ($expiredUsers as $user) {
            if ($user->getBaiduUid() === $validUser->getBaiduUid()) {
                $validUserInExpired = true;
                break;
            }
        }
        $this->assertFalse($validUserInExpired);
    }

    public function testFindExpiredTokenUsersReturnsEmptyWhenNoExpiredUsers(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        // 清理现有的过期用户
        $existingExpiredUsers = $repository->findExpiredTokenUsers();
        foreach ($existingExpiredUsers as $user) {
            $repository->remove($user);
        }

        $config = $this->createConfig();
        $validUser = $this->createBaiduUser('valid_uid_' . uniqid(), $config);
        $repository->save($validUser);

        $expiredUsers = $repository->findExpiredTokenUsers();
        $this->assertEmpty($expiredUsers);
    }

    public function testSaveAndRemoveWithFlushTrue(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $user = $this->createBaiduUser('test_uid_' . uniqid(), $config);

        // 保存
        $repository->save($user, true);
        $this->assertNotNull($user->getId());

        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser);

        // 删除
        $userId = $user->getId();
        $repository->remove($user, true);
        $foundUser = $repository->find($userId);
        $this->assertNull($foundUser);
    }

    public function testSaveAndRemoveWithFlushFalse(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $user = $this->createBaiduUser('test_uid_' . uniqid(), $config);

        // 保存但不立即flush
        $repository->save($user, false);
        self::getEntityManager()->flush();
        $this->assertNotNull($user->getId());

        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser);

        // 删除但不立即flush
        $repository->remove($user, false);
        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser); // 仍然存在，因为还没flush

        // 手动flush
        $userId = $user->getId();
        self::getEntityManager()->flush();
        $foundUser = $repository->find($userId);
        $this->assertNull($foundUser); // 现在应该被删除了
    }

    public function testUserWithTokenExpiredStatus(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();

        // 创建已经过期的用户（直接设置过期时间为过去）
        $expiredUser = new BaiduOAuth2User();
        $expiredUser->setBaiduUid('expired_uid_' . uniqid());
        $expiredUser->setAccessToken('access_token');
        $expiredUser->setExpiresIn(3600);
        $expiredUser->setConfig($config);
        // 直接设置为1小时前过期
        $expiredUser->setExpireTime((new \DateTimeImmutable())->modify('-1 hour'));
        $repository->save($expiredUser);

        // 检查Token是否过期
        $this->assertTrue($expiredUser->isTokenExpired());

        // 通过查询验证该用户出现在过期用户列表中
        $expiredUsers = $repository->findExpiredTokenUsers();
        $foundExpired = false;
        foreach ($expiredUsers as $expiredUserFromQuery) {
            if ($expiredUserFromQuery->getBaiduUid() === $expiredUser->getBaiduUid()) {
                $foundExpired = true;
                break;
            }
        }
        $this->assertTrue($foundExpired);
    }

    public function testFindByConfigFilter(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config1 = $this->createConfig();
        $config2 = $this->createConfig();

        $user1 = $this->createBaiduUser('user1_' . uniqid(), $config1);
        $user2 = $this->createBaiduUser('user2_' . uniqid(), $config2);

        $repository->save($user1);
        $repository->save($user2);

        // 按config查找
        $usersForConfig1 = $repository->findBy(['config' => $config1]);
        $this->assertGreaterThanOrEqual(1, count($usersForConfig1));

        $found = false;
        foreach ($usersForConfig1 as $user) {
            if ($user->getBaiduUid() === $user1->getBaiduUid()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testUserWithRefreshToken(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $user = $this->createBaiduUser('user_with_refresh_' . uniqid(), $config);
        $refreshToken = 'refresh_token_' . uniqid();
        $user->setRefreshToken($refreshToken);
        $repository->save($user);

        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals($refreshToken, $foundUser->getRefreshToken());
    }

    public function testUserWithUserProfile(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $user = $this->createBaiduUser('user_with_profile_' . uniqid(), $config);

        $username = 'test_username_' . uniqid();
        $avatar = 'https://example.com/avatar.png';
        $rawData = ['key' => 'value', 'timestamp' => time()];

        $user->setUsername($username);
        $user->setAvatar($avatar);
        $user->setRawData($rawData);

        $repository->save($user);

        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals($username, $foundUser->getUsername());
        $this->assertEquals($avatar, $foundUser->getAvatar());
        $this->assertEquals($rawData, $foundUser->getRawData());
    }

    public function testFindByBaiduUidUniqueness(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(BaiduOAuth2UserRepository::class, $repository);

        $config = $this->createConfig();
        $baiduUid = 'unique_uid_' . uniqid();

        $user1 = $this->createBaiduUser($baiduUid, $config);
        $repository->save($user1);

        // 尝试创建具有相同baiduUid的用户应该会失败（通过数据库约束）
        $user2 = $this->createBaiduUser($baiduUid, $config);

        $this->expectException(UniqueConstraintViolationException::class);
        $repository->save($user2);
    }

    protected function createNewEntity(): object
    {
        $config = $this->createConfig();

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid_' . uniqid());
        $user->setAccessToken('access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        return $user;
    }

    /**
     * @return ServiceEntityRepository<BaiduOAuth2User>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(BaiduOAuth2UserRepository::class);
    }

    private function createConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $config->setScope('basic');

        return $config;
    }

    private function createBaiduUser(string $baiduUid, BaiduOAuth2Config $config): BaiduOAuth2User
    {
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken('access_token_' . uniqid());
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        return $user;
    }

    private function createExpiredUser(string $baiduUid, BaiduOAuth2Config $config): BaiduOAuth2User
    {
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($baiduUid);
        $user->setAccessToken('expired_access_token_' . uniqid());
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        // 设置过期时间为1小时前
        $user->setExpireTime(new \DateTimeImmutable('-1 hour'));

        return $user;
    }
}
