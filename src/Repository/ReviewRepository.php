<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review>
     */
    public function findAllOrderedByNewest(?string $companyQuery = null): array
    {
        $queryBuilder = $this->createQueryBuilder('review')
            ->orderBy('review.createdAt', 'DESC')
            ->addOrderBy('review.id', 'DESC');

        if (null !== $companyQuery) {
            $queryBuilder
                ->andWhere('LOWER(review.companyName) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$companyQuery.'%');
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{
     *     companyName: string,
     *     reviewCount: int,
     *     averageRating: float
     * }>
     */
    public function findCompanyStatistics(): array
    {
        /** @var list<array{companyName: mixed, reviewCount: mixed, averageRating: mixed}> $statistics */
        $statistics = $this->createQueryBuilder('review')
            ->select('review.companyName AS companyName')
            ->addSelect('COUNT(review.id) AS reviewCount')
            ->addSelect('AVG(review.rating) AS averageRating')
            ->groupBy('review.companyName')
            ->orderBy('averageRating', 'DESC')
            ->addOrderBy('reviewCount', 'DESC')
            ->addOrderBy('companyName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $statistic): array => [
                'companyName' => (string) $statistic['companyName'],
                'reviewCount' => (int) $statistic['reviewCount'],
                'averageRating' => (float) $statistic['averageRating'],
            ],
            $statistics,
        );
    }
}
