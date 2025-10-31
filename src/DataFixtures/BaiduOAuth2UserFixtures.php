<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;

class BaiduOAuth2UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var BaiduOAuth2Config $config */
        $config = $this->getReference(BaiduOAuth2ConfigFixtures::BAIDU_CONFIG_REFERENCE, BaiduOAuth2Config::class);

        // 创建有效的用户
        $activeUser = new BaiduOAuth2User();
        $activeUser->setBaiduUid('baidu_uid_' . bin2hex(random_bytes(8)));
        $activeUser->setAccessToken('access_token_' . bin2hex(random_bytes(32)));
        $activeUser->setExpiresIn(3600);
        $activeUser->setConfig($config);
        $activeUser->setRefreshToken('refresh_token_' . bin2hex(random_bytes(32)));
        $activeUser->setUsername('测试用户1');
        $activeUser->setAvatar(null);
        $activeUser->setRawData([
            'userid' => $activeUser->getBaiduUid(),
            'username' => '测试用户1',
            'portrait' => 'avatar1',
            'userdetail' => '百度用户详细信息',
        ]);
        $manager->persist($activeUser);

        // 创建过期token的用户
        $expiredUser = new BaiduOAuth2User();
        $expiredUser->setBaiduUid('baidu_uid_' . bin2hex(random_bytes(8)));
        $expiredUser->setAccessToken('expired_token_' . bin2hex(random_bytes(32)));
        $expiredUser->setExpiresIn(3600);
        $expiredUser->setConfig($config);
        $expiredUser->setExpireTime((new \DateTimeImmutable())->modify('-1 hour'));
        $expiredUser->setUsername('过期用户');
        $expiredUser->setAvatar(null);
        $expiredUser->setRawData([
            'userid' => $expiredUser->getBaiduUid(),
            'username' => '过期用户',
            'portrait' => 'expired_avatar',
        ]);
        $manager->persist($expiredUser);

        // 创建最小信息用户
        $minimalUser = new BaiduOAuth2User();
        $minimalUser->setBaiduUid('minimal_uid_' . bin2hex(random_bytes(8)));
        $minimalUser->setAccessToken('minimal_token_' . bin2hex(random_bytes(32)));
        $minimalUser->setExpiresIn(7200);
        $minimalUser->setConfig($config);
        $manager->persist($minimalUser);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BaiduOAuth2ConfigFixtures::class,
        ];
    }
}
