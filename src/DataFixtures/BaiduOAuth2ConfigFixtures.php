<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;

class BaiduOAuth2ConfigFixtures extends Fixture
{
    public const BAIDU_CONFIG_REFERENCE = 'baidu-config';

    public function load(ObjectManager $manager): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_baidu_client_id_123456');
        $config->setClientSecret('test_baidu_client_secret_abc789');
        $config->setScope('basic,netdisk');
        $config->setValid(true);

        $manager->persist($config);

        // 添加一个无效的配置用于测试
        $invalidConfig = new BaiduOAuth2Config();
        $invalidConfig->setClientId('invalid_client_id');
        $invalidConfig->setClientSecret('invalid_client_secret');
        $invalidConfig->setScope('basic');
        $invalidConfig->setValid(false);

        $manager->persist($invalidConfig);

        $manager->flush();

        // 设置引用，供其他 Fixtures 使用
        $this->addReference(self::BAIDU_CONFIG_REFERENCE, $config);
    }
}
