<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    private const COMPANY_SUGGESTION_LIMIT = 100;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review>
     */
    public function findAllOrderedByNewest(?string $companyQuery = null): array
    {
        $queryBuilder = $this->createNewestFirstQueryBuilder();

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

        return array_map($this->normalizeCompanyStatistic(...), $statistics);
    }

    /**
     * @return list<Review>
     */
    public function findByCompanyNameOrderedByNewest(string $companyName): array
    {
        return $this->createNewestFirstQueryBuilder()
            ->andWhere('LOWER(review.companyName) = LOWER(:companyName)')
            ->setParameter('companyName', $companyName)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{
     *     companyName: string,
     *     reviewCount: int,
     *     averageRating: float
     * }|null
     */
    public function findCompanyStatisticsByName(string $companyName): ?array
    {
        /** @var list<array{companyName: mixed, reviewCount: mixed, averageRating: mixed}> $statistics */
        $statistics = $this->createQueryBuilder('review')
            ->select('review.companyName AS companyName')
            ->addSelect('COUNT(review.id) AS reviewCount')
            ->addSelect('AVG(review.rating) AS averageRating')
            ->andWhere('LOWER(review.companyName) = LOWER(:companyName)')
            ->setParameter('companyName', $companyName)
            ->groupBy('review.companyName')
            ->getQuery()
            ->getArrayResult();

        if ([] === $statistics) {
            return null;
        }

        return $this->normalizeCompanyStatistic($statistics[0]);
    }

    /**
     * @return list<string>
     */
    public function findCompanyNameSuggestions(): array
    {
        /** @var list<array{companyName: mixed}> $companyNames */
        $companyNames = $this->createQueryBuilder('review')
            ->select('DISTINCT review.companyName AS companyName')
            ->orderBy('review.companyName', 'ASC')
            ->setMaxResults(self::COMPANY_SUGGESTION_LIMIT)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $company): string => (string) $company['companyName'],
            $companyNames,
        );
    }

    public function findCanonicalCompanyName(string $companyName): ?string
    {
        /** @var list<array{companyName: mixed}> $matches */
        $matches = $this->createQueryBuilder('review')
            ->select('review.companyName AS companyName')
            ->andWhere('LOWER(review.companyName) = LOWER(:companyName)')
            ->setParameter('companyName', $companyName)
            ->orderBy('review.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        if ([] === $matches) {
            return null;
        }

        return (string) $matches[0]['companyName'];
    }

    private function createNewestFirstQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('review')
            ->orderBy('review.createdAt', 'DESC')
            ->addOrderBy('review.id', 'DESC');
    }

    /**
     * @param array{companyName: mixed, reviewCount: mixed, averageRating: mixed} $statistic
     *
     * @return array{companyName: string, reviewCount: int, averageRating: float}
     */
    private function normalizeCompanyStatistic(array $statistic): array
    {
        return [
            'companyName' => (string) $statistic['companyName'],
            'reviewCount' => (int) $statistic['reviewCount'],
            'averageRating' => (float) $statistic['averageRating'],
        ];
    }
}
