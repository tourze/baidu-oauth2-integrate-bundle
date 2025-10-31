<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2StateRepository;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: BaiduOAuth2StateRepository::class)]
#[ORM\Table(name: 'baidu_oauth2_state', options: ['comment' => 'Baidu OAuth2 状态表'])]
class BaiduOAuth2State implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'state 值'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $state;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '过期时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private \DateTimeImmutable $expireTime;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用'])]
    #[Assert\Type(type: 'bool')]
    private bool $used = false;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '会话ID'])]
    #[Assert\Length(max: 128)]
    private ?string $sessionId = null;

    #[ORM\ManyToOne(targetEntity: BaiduOAuth2Config::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private BaiduOAuth2Config $config;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return sprintf('Baidu OAuth2 State %s', $this->state);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getExpireTime(): \DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function isExpired(): bool
    {
        return $this->expireTime < new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getConfig(): BaiduOAuth2Config
    {
        return $this->config;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setConfig(BaiduOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function setExpireTime(\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }
}
