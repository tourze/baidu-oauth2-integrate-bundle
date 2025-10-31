<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Repository\BaiduOAuth2UserRepository;

class BaiduUserManager
{
    private const USER_INFO_URL = 'https://openapi.baidu.com/rest/2.0/passport/users/getInfo';

    public function __construct(
        private BaiduApiClient $apiClient,
        private BaiduOAuth2UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 拉取用户信息
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $accessToken): array
    {
        $requestOptions = [
            'query' => [
                'access_token' => $accessToken,
            ],
            'headers' => $this->apiClient->getDefaultHeaders('application/json'),
        ];

        $response = $this->apiClient->makeRequest('user info request', self::USER_INFO_URL, $requestOptions);
        $data = json_decode($response['content'], true);
        if (!is_array($data)) {
            throw new BaiduOAuth2Exception('Invalid user info response from Baidu');
        }

        // Ensure all keys are strings
        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * 获取用户信息（缓存/强制刷新）
     * @return array<string, mixed>
     */
    public function getUserInfo(string $baiduUid, bool $forceRefresh = false): array
    {
        $user = $this->userRepository->findByBaiduUid($baiduUid);
        if (null === $user) {
            throw new BaiduOAuth2Exception('User not found');
        }

        if (!$forceRefresh && !$user->isTokenExpired() && null !== $user->getRawData()) {
            return $user->getRawData();
        }

        $data = $this->fetchUserInfo($user->getAccessToken());
        $this->applyProfile($user, $data);
        $user->setRawData($data);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $data;
    }

    /**
     * 创建或更新用户
     * @param array<string, mixed> $data
     */
    public function updateOrCreateUser(array $data, BaiduOAuth2Config $config): BaiduOAuth2User
    {
        $uid = $this->extractUserId($data);
        $user = $this->userRepository->findByBaiduUid($uid);

        if (null === $user) {
            $user = $this->createNewUser($data, $uid, $config);
        } else {
            $this->updateExistingUser($user, $data);
        }

        $this->finalizeUser($user, $data);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractUserId(array $data): string
    {
        $uidValue = $data['userid'] ?? $data['uid'] ?? $data['baidu_uid'] ?? '';
        if (!is_string($uidValue) || '' === $uidValue) {
            throw new BaiduOAuth2Exception('Missing or invalid Baidu user id');
        }

        return $uidValue;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createNewUser(array $data, string $uid, BaiduOAuth2Config $config): BaiduOAuth2User
    {
        $accessTokenValue = $data['access_token'] ?? '';
        $expiresInValue = $data['expires_in'] ?? 0;

        $accessToken = is_string($accessTokenValue) ? $accessTokenValue : '';
        $expiresIn = is_int($expiresInValue) ? $expiresInValue : 0;

        $user = new BaiduOAuth2User();
        $user->setBaiduUid($uid);
        $user->setAccessToken($accessToken);
        $user->setExpiresIn($expiresIn);
        $user->setConfig($config);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateExistingUser(BaiduOAuth2User $user, array $data): void
    {
        if (isset($data['access_token']) && is_string($data['access_token'])) {
            $user->setAccessToken($data['access_token']);
        }
        if (isset($data['expires_in']) && is_int($data['expires_in'])) {
            $user->setExpiresIn($data['expires_in']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function finalizeUser(BaiduOAuth2User $user, array $data): void
    {
        if (isset($data['refresh_token']) && is_string($data['refresh_token'])) {
            $user->setRefreshToken($data['refresh_token']);
        }

        $this->applyProfile($user, $data);
        $user->setRawData($data);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * 合并 token 和 userinfo 数据（供 orchestrator 使用）
     * @param array<string, mixed> $tokenData
     * @param array<string, mixed> $userInfo
     * @return array<string, mixed>
     */
    public function mergeUserData(array $tokenData, array $userInfo): array
    {
        return array_merge($tokenData, $userInfo);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyProfile(BaiduOAuth2User $user, array $data): void
    {
        if (isset($data['username']) && is_string($data['username'])) {
            $user->setUsername($data['username']);
        }
        // Baidu头像 portrait 需要拼接，常见是 http://himg.bdimg.com/sys/portrait/item/ + portrait
        if (isset($data['portrait']) && is_string($data['portrait'])) {
            $portrait = $data['portrait'];
            if ('' !== $portrait) {
                $user->setAvatar('https://himg.bdimg.com/sys/portrait/item/' . $portrait);
            }
        }
    }

    public function findUserById(mixed $userId): ?BaiduOAuth2User
    {
        if (null === $userId) {
            return null;
        }

        return $this->userRepository->find($userId);
    }

    /**
     * @return BaiduOAuth2User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }
}
