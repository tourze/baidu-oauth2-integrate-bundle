<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2ConfigRepository;

#[Autoconfigure(public: true)]
class BaiduOAuth2Service
{
    public function __construct(
        private BaiduOAuth2ConfigRepository $configRepository,
        private BaiduStateManager $stateManager,
        private BaiduTokenManager $tokenManager,
        private BaiduUserManager $userManager,
    ) {
    }

    public function generateAuthorizationUrl(?string $sessionId = null): string
    {
        $config = $this->configRepository->findValidConfig();
        if (null === $config) {
            throw new BaiduOAuth2Exception('No valid Baidu OAuth2 configuration found');
        }

        return $this->stateManager->generateAuthorizationUrl($config, $sessionId);
    }

    public function handleCallback(string $code, string $state): BaiduOAuth2User
    {
        $stateEntity = $this->stateManager->validateAndMarkStateAsUsed($state);
        $config = $stateEntity->getConfig();
        $redirectUri = $this->stateManager->generateRedirectUri();

        $tokenData = $this->tokenManager->exchangeCodeForToken(
            $code,
            $config->getClientId(),
            $config->getClientSecret(),
            $redirectUri
        );

        $accessTokenValue = $tokenData['access_token'] ?? '';
        if (!is_string($accessTokenValue) || '' === $accessTokenValue) {
            throw new BaiduOAuth2Exception('Missing or invalid access_token in token response');
        }
        $accessToken = $accessTokenValue;
        $userInfo = $this->userManager->fetchUserInfo($accessToken);
        $merged = $this->userManager->mergeUserData($tokenData, $userInfo);

        return $this->userManager->updateOrCreateUser($merged, $config);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserInfo(string $baiduUid, bool $forceRefresh = false): array
    {
        return $this->userManager->getUserInfo($baiduUid, $forceRefresh);
    }
}
