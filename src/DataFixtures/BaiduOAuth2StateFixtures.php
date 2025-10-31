<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;

class BaiduOAuth2StateFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var BaiduOAuth2Config $config */
        $config = $this->getReference(BaiduOAuth2ConfigFixtures::BAIDU_CONFIG_REFERENCE, BaiduOAuth2Config::class);

        // 创建一个有效的state
        $validState = new BaiduOAuth2State();
        $validState->setState('test_state_' . bin2hex(random_bytes(16)));
        $validState->setConfig($config);
        $validState->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));
        $validState->setSessionId('test_session_' . bin2hex(random_bytes(8)));
        $manager->persist($validState);

        // 创建一个已使用的state
        $usedState = new BaiduOAuth2State();
        $usedState->setState('used_state_' . bin2hex(random_bytes(16)));
        $usedState->setConfig($config);
        $usedState->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));
        $usedState->setSessionId('used_session_' . bin2hex(random_bytes(8)));
        $usedState->markAsUsed();
        $manager->persist($usedState);

        // 创建一个过期的state
        $expiredState = new BaiduOAuth2State();
        $expiredState->setState('expired_state_' . bin2hex(random_bytes(16)));
        $expiredState->setConfig($config);
        $expiredState->setSessionId('expired_session_' . bin2hex(random_bytes(8)));
        $expiredState->setExpireTime((new \DateTimeImmutable())->modify('-1 hour'));
        $manager->persist($expiredState);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BaiduOAuth2ConfigFixtures::class,
        ];
    }
}
