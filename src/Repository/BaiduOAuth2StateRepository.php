<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<BaiduOAuth2State>
 */
#[AsRepository(entityClass: BaiduOAuth2State::class)]
class BaiduOAuth2StateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaiduOAuth2State::class);
    }

    public function findValidState(string $state): ?BaiduOAuth2State
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.state = :state')
            ->andWhere('s.used = :used')
            ->andWhere('s.expireTime > :now')
            ->setParameter('state', $state)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof BaiduOAuth2State ? $result : null;
    }

    public function cleanupExpiredStates(): int
    {
        $result = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expireTime < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;

        return is_int($result) ? $result : 0;
    }

    public function save(BaiduOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BaiduOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
