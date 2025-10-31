<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2StateRepository;

class BaiduStateManager
{
    private const AUTHORIZE_URL = 'https://openapi.baidu.com/oauth/2.0/authorize';

    public function __construct(
        private BaiduOAuth2StateRepository $stateRepository,
        private EntityManagerInterface $entityManager,
        private ?UrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    public function generateAuthorizationUrl(BaiduOAuth2Config $config, ?string $sessionId = null): string
    {
        $state = bin2hex(random_bytes(16));
        $stateEntity = new BaiduOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);
        $stateEntity->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));
        if (null !== $sessionId) {
            $stateEntity->setSessionId($sessionId);
        }
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();

        $redirectUri = $this->generateRedirectUri();
        $params = [
            'response_type' => 'code',
            'client_id' => $config->getClientId(),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ];
        if (null !== $config->getScope() && '' !== $config->getScope()) {
            $params['scope'] = $config->getScope();
        } else {
            $params['scope'] = 'basic';
        }

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    public function validateAndMarkStateAsUsed(string $state): BaiduOAuth2State
    {
        $stateEntity = $this->stateRepository->findValidState($state);
        if (null === $stateEntity || !$stateEntity->isValid()) {
            throw new BaiduOAuth2Exception('Invalid or expired state');
        }

        $stateEntity->markAsUsed();
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();

        return $stateEntity;
    }

    public function cleanupExpiredStates(): int
    {
        return $this->stateRepository->cleanupExpiredStates();
    }

    public function generateRedirectUri(): string
    {
        if (null === $this->urlGenerator) {
            throw new BaiduOAuth2Exception('UrlGeneratorInterface is required to generate authorization URL');
        }

        return $this->urlGenerator->generate('baidu_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
