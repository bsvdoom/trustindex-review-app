<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReviewRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReviewRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = $this->entityManager->getRepository(Review::class);
        self::assertInstanceOf(ReviewRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testCompanyStatisticsCalculateAggregatesAndUseDeterministicOrdering(): void
    {
        $this->createReview('Beta', 5);
        $this->createReview('Beta', 5);
        $this->createReview('Alpha', 5);
        $this->createReview('Alpha', 3);
        $this->createReview('Delta', 4);
        $this->createReview('Gamma', 4);
        $this->entityManager->flush();

        $statistics = $this->repository->findCompanyStatistics();

        self::assertCount(4, $statistics);
        self::assertSame(['Beta', 'Alpha', 'Delta', 'Gamma'], array_column($statistics, 'companyName'));
        self::assertSame(
            [
                ['companyName' => 'Beta', 'reviewCount' => 2, 'averageRating' => 5.0],
                ['companyName' => 'Alpha', 'reviewCount' => 2, 'averageRating' => 4.0],
                ['companyName' => 'Delta', 'reviewCount' => 1, 'averageRating' => 4.0],
                ['companyName' => 'Gamma', 'reviewCount' => 1, 'averageRating' => 4.0],
            ],
            $statistics,
        );

        foreach ($statistics as $statistic) {
            self::assertIsString($statistic['companyName']);
            self::assertIsInt($statistic['reviewCount']);
            self::assertIsFloat($statistic['averageRating']);
        }
    }

    public function testExactCompanyQueriesAreCaseInsensitiveOrderedAndReturnAggregates(): void
    {
        $first = $this->createReview('Acme Kft.', 2);
        $second = $this->createReview('ACME KFT.', 4);
        $this->createReview('Acme Holding', 5);
        $this->entityManager->flush();

        $reviews = $this->repository->findByCompanyNameOrderedByNewest('acme kft.');

        self::assertCount(2, $reviews);
        self::assertSame($second->getId(), $reviews[0]->getId());
        self::assertSame($first->getId(), $reviews[1]->getId());
        self::assertSame([], $this->repository->findByCompanyNameOrderedByNewest('Acme'));

        $statistics = $this->repository->findCompanyStatisticsByName('aCmE kFt.');
        self::assertNotNull($statistics);
        self::assertSame(2, $statistics['reviewCount']);
        self::assertSame(3.0, $statistics['averageRating']);
        self::assertSame([], $this->repository->findByCompanyNameOrderedByNewest('Nem létező cég'));
        self::assertNull($this->repository->findCompanyStatisticsByName('Nem létező cég'));
    }

    public function testCanonicalCompanyNameUsesTheSmallestIdSpelling(): void
    {
        $this->createReview('Acme Kft.', 5);
        $this->entityManager->flush();
        $this->createReview('ACME KFT.', 4);
        $this->entityManager->flush();

        self::assertSame('Acme Kft.', $this->repository->findCanonicalCompanyName('acme kft.'));
        self::assertNull($this->repository->findCanonicalCompanyName('Acme'));
        self::assertNull($this->repository->findCanonicalCompanyName('Nem létező cég'));
    }

    public function testCompanyNameSuggestionsAreDistinctOrderedStrings(): void
    {
        $this->createReview('Zulu', 3);
        $this->createReview('Alpha', 4);
        $this->createReview('Beta', 5);
        $this->createReview('Alpha', 2);
        $this->entityManager->flush();

        $suggestions = $this->repository->findCompanyNameSuggestions();

        self::assertSame(['Alpha', 'Beta', 'Zulu'], $suggestions);
        self::assertCount(3, $suggestions);
        self::assertContainsOnlyString($suggestions);
    }

    private function createReview(string $companyName, int $rating): Review
    {
        $review = (new Review())
            ->setCompanyName($companyName)
            ->setRating($rating)
            ->setReviewText(sprintf('%s tesztvélemény.', $companyName))
            ->setAuthorEmail('reviewer@example.com');

        $this->entityManager->persist($review);

        return $review;
    }
}
