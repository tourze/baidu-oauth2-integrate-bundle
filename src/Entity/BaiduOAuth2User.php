<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2UserRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: BaiduOAuth2UserRepository::class)]
#[ORM\Table(name: 'baidu_oauth2_user', options: ['comment' => 'Baidu OAuth2 用户表'])]
class BaiduOAuth2User implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    // Baidu 用户唯一标识（用户信息接口返回的 userid）
    #[ORM\Column(type: Types::STRING, length: 128, unique: true, options: ['comment' => 'Baidu 用户ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $baiduUid;

    // 访问令牌与过期
    #[ORM\Column(type: Types::STRING, length: 512, options: ['comment' => '访问令牌'])]
    #[Assert\NotBlank]
    private string $accessToken;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '令牌有效期（秒）'])]
    #[Assert\Positive]
    private int $expiresIn;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '令牌过期时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private \DateTimeImmutable $expireTime;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '刷新令牌'])]
    #[Assert\Length(max: 512)]
    private ?string $refreshToken = null;

    // 用户资料（按百度返回字段，可选）
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户名'])]
    #[Assert\Length(max: 255)]
    private ?string $username = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '头像URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $avatar = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始返回数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $rawData = null;

    #[ORM\ManyToOne(targetEntity: BaiduOAuth2Config::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private BaiduOAuth2Config $config;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return sprintf('Baidu OAuth2 User %s', $this->baiduUid);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaiduUid(): string
    {
        return $this->baiduUid;
    }

    public function setBaiduUid(string $uid): void
    {
        $this->baiduUid = $uid;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(int $seconds): void
    {
        $this->expiresIn = $seconds;
        $this->expireTime = (new \DateTimeImmutable())->modify("+{$seconds} seconds");
    }

    public function getExpireTime(): \DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(\DateTimeInterface $expireTime): void
    {
        $this->expireTime = $expireTime instanceof \DateTimeImmutable ? $expireTime : \DateTimeImmutable::createFromInterface($expireTime);
    }

    public function isTokenExpired(): bool
    {
        return $this->expireTime <= new \DateTimeImmutable();
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $token): void
    {
        $this->refreshToken = $token;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $name): void
    {
        $this->username = $name;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    public function setRawData(?array $raw): void
    {
        $this->rawData = $raw;
    }

    public function getConfig(): BaiduOAuth2Config
    {
        return $this->config;
    }

    public function setConfig(BaiduOAuth2Config $config): void
    {
        $this->config = $config;
    }
}
