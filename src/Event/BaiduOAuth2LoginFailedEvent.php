<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BaiduOAuth2LoginFailedEvent extends Event
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $reason,
        private readonly array $context = [],
    ) {
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
