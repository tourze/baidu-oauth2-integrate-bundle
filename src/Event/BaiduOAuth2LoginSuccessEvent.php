<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;

final class BaiduOAuth2LoginSuccessEvent extends Event
{
    public function __construct(
        private readonly BaiduOAuth2User $user,
    ) {
    }

    public function getUser(): BaiduOAuth2User
    {
        return $this->user;
    }
}
