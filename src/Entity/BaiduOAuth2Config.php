<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2ConfigRepository;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: BaiduOAuth2ConfigRepository::class)]
#[ORM\Table(name: 'baidu_oauth2_config', options: ['comment' => 'Baidu OAuth2 配置表'])]
class BaiduOAuth2Config implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    // Baidu API Key
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Baidu API Key'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientId = '';

    // Baidu Secret Key
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Baidu Secret Key'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientSecret = '';

    // OAuth scope, nullable
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '授权范围'])]
    #[Assert\Length(max: 65535)]
    private ?string $scope = null;

    // Whether this config is active
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $valid = true;

    public function __toString(): string
    {
        return sprintf('Baidu OAuth2 Config #%d (%s)', $this->id ?? 0, $this->clientId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }
}
