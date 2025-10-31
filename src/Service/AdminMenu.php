<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('OAuth2认证')) {
            $item->addChild('OAuth2认证');
        }

        $oauth2Menu = $item->getChild('OAuth2认证');
        if (null === $oauth2Menu) {
            return;
        }

        $oauth2Menu->addChild('Baidu配置')->setUri($this->linkGenerator->getCurdListPage(BaiduOAuth2Config::class));
        $oauth2Menu->addChild('授权用户')->setUri($this->linkGenerator->getCurdListPage(BaiduOAuth2User::class));
        $oauth2Menu->addChild('状态管理')->setUri($this->linkGenerator->getCurdListPage(BaiduOAuth2State::class));
    }
}
