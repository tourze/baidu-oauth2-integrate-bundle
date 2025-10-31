<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<BaiduOAuth2User>
 */
#[AsRepository(entityClass: BaiduOAuth2User::class)]
class BaiduOAuth2UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaiduOAuth2User::class);
    }

    public function findByBaiduUid(string $uid): ?BaiduOAuth2User
    {
        return $this->findOneBy(['baiduUid' => $uid]);
    }

    /**
     * @return BaiduOAuth2User[]
     */
    public function findExpiredTokenUsers(): array
    {
        $result = $this->createQueryBuilder('u')
            ->andWhere('u.expireTime < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        // Filter to ensure all items are BaiduOAuth2User instances
        $filteredResult = [];
        foreach ($result as $item) {
            if ($item instanceof BaiduOAuth2User) {
                $filteredResult[] = $item;
            }
        }

        return $filteredResult;
    }

    public function save(BaiduOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BaiduOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
