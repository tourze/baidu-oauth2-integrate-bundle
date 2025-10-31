<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;

#[WithMonologChannel(channel: 'baidu_oauth2_integrate')]
class BaiduTokenManager
{
    private const TOKEN_URL = 'https://openapi.baidu.com/oauth/2.0/token';

    public function __construct(
        private BaiduApiClient $apiClient,
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCodeForToken(string $code, string $clientId, string $clientSecret, string $redirectUri): array
    {
        $requestOptions = [
            'query' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
            ],
            'headers' => $this->apiClient->getDefaultHeaders('application/json'),
        ];

        $context = ['client_id' => $clientId, 'redirect_uri' => $redirectUri];
        $response = $this->apiClient->makeRequest('token exchange', self::TOKEN_URL, $requestOptions, $context);

        return $this->parseTokenResponse($response['content']);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshToken(BaiduOAuth2User $user): array
    {
        if (null === $user->getRefreshToken()) {
            return [];
        }

        $config = $user->getConfig();
        $requestOptions = [
            'query' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->getRefreshToken(),
                'client_id' => $config->getClientId(),
                'client_secret' => $config->getClientSecret(),
            ],
            'headers' => $this->apiClient->getDefaultHeaders('application/json'),
        ];

        $context = ['baidu_uid' => substr($user->getBaiduUid(), 0, 8) . '***'];
        $response = $this->apiClient->makeRequest('refresh token', self::TOKEN_URL, $requestOptions, $context);
        $data = $this->parseTokenResponse($response['content']);

        if (isset($data['access_token']) && is_string($data['access_token'])) {
            $user->setAccessToken($data['access_token']);
        }
        if (isset($data['expires_in']) && is_int($data['expires_in'])) {
            $user->setExpiresIn($data['expires_in']);
        }
        if (isset($data['refresh_token']) && is_string($data['refresh_token'])) {
            $user->setRefreshToken($data['refresh_token']);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseTokenResponse(string $content): array
    {
        $data = json_decode($content, true);
        if (is_array($data)) {
            // Ensure all keys are strings
            $result = [];
            foreach ($data as $key => $value) {
                $result[(string) $key] = $value;
            }

            return $result;
        }

        // Fallback if response is urlencoded (defensive)
        $params = [];
        parse_str($content, $params);
        if ([] !== $params) {
            // Convert to expected array type
            $result = [];
            foreach ($params as $key => $value) {
                $result[(string) $key] = $value;
            }

            return $result;
        }

        $this->logger?->error('Baidu OAuth2 token response parse failed', ['content_head' => substr($content, 0, 200)]);
        throw new BaiduOAuth2Exception('Invalid token response from Baidu');
    }
}
