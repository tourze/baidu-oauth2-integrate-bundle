<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<BaiduOAuth2Config>
 *
 * @method void clearCache()
 */
#[AsRepository(entityClass: BaiduOAuth2Config::class)]
final class BaiduOAuth2ConfigRepository extends ServiceEntityRepository
{
    private const CACHE_TTL = 3600;
    private const CACHE_KEY_VALID_CONFIG = 'baidu_oauth2.valid_config';
    private const CACHE_KEY_CLIENT_CONFIG = 'baidu_oauth2.client_config.%s';

    public function __construct(
        ManagerRegistry $registry,
        private ?CacheInterface $cache = null,
    ) {
        parent::__construct($registry, BaiduOAuth2Config::class);
    }

    public function findValidConfig(): ?BaiduOAuth2Config
    {
        if (null === $this->cache) {
            return $this->findValidConfigFromDatabase();
        }

        return $this->cache->get(self::CACHE_KEY_VALID_CONFIG, function (ItemInterface $item): ?BaiduOAuth2Config {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->findValidConfigFromDatabase();
        });
    }

    private function findValidConfigFromDatabase(): ?BaiduOAuth2Config
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof BaiduOAuth2Config ? $result : null;
    }

    public function findByClientId(string $clientId): ?BaiduOAuth2Config
    {
        if (null === $this->cache) {
            return $this->findByClientIdFromDatabase($clientId);
        }

        $cacheKey = sprintf(self::CACHE_KEY_CLIENT_CONFIG, $clientId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($clientId): ?BaiduOAuth2Config {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->findByClientIdFromDatabase($clientId);
        });
    }

    private function findByClientIdFromDatabase(string $clientId): ?BaiduOAuth2Config
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof BaiduOAuth2Config ? $result : null;
    }

    public function save(BaiduOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->clearCache();
        }
    }

    public function remove(BaiduOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->clearCache();
        }
    }

    public function clearCache(): void
    {
        $this->cache?->delete(self::CACHE_KEY_VALID_CONFIG);
    }
}
