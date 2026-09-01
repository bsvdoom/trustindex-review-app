<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Review;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReviewTest extends TestCase
{
    public function testConstructorInitializesMatchingImmutableTimestamps(): void
    {
        $review = new Review();

        self::assertInstanceOf(\DateTimeImmutable::class, $review->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $review->getUpdatedAt());
        self::assertSame($review->getCreatedAt(), $review->getUpdatedAt());
    }

    public function testTextSettersTrimValuesWithoutChangingEmailCase(): void
    {
        $review = (new Review())
            ->setCompanyName('  Acme Kft.  ')
            ->setReviewText("  Megbízható szolgáltatás.\n")
            ->setAuthorEmail('  Author@Example.COM  ');

        self::assertSame('Acme Kft.', $review->getCompanyName());
        self::assertSame('Megbízható szolgáltatás.', $review->getReviewText());
        self::assertSame('Author@Example.COM', $review->getAuthorEmail());
    }

    public function testPreUpdateCallbackReplacesUpdatedAtWithANonEarlierInstance(): void
    {
        $review = new Review();
        $previousUpdatedAt = $review->getUpdatedAt();

        $review->updateUpdatedAt();

        self::assertNotSame($previousUpdatedAt, $review->getUpdatedAt());
        self::assertGreaterThanOrEqual($previousUpdatedAt, $review->getUpdatedAt());
    }

    public function testValidReviewHasNoValidationViolations(): void
    {
        self::assertCount(0, $this->validator()->validate($this->validReview()));
    }

    #[DataProvider('invalidRatingProvider')]
    public function testRatingOutsideAllowedRangeIsInvalid(int $rating): void
    {
        $review = $this->validReview()->setRating($rating);

        self::assertGreaterThan(0, $this->validator()->validate($review)->count());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidRatingProvider(): iterable
    {
        yield 'below minimum' => [0];
        yield 'above maximum' => [6];
    }

    public function testInvalidEmailIsRejected(): void
    {
        $review = $this->validReview()->setAuthorEmail('nem-email');

        self::assertGreaterThan(0, $this->validator()->validate($review)->count());
    }

    public function testBlankCompanyNameIsRejected(): void
    {
        $review = $this->validReview()->setCompanyName('   ');

        self::assertGreaterThan(0, $this->validator()->validate($review)->count());
    }

    public function testBlankReviewTextIsRejected(): void
    {
        $review = $this->validReview()->setReviewText(" \n ");

        self::assertGreaterThan(0, $this->validator()->validate($review)->count());
    }

    private function validReview(): Review
    {
        return (new Review())
            ->setCompanyName('Acme Kft.')
            ->setRating(5)
            ->setReviewText('Kiváló szolgáltatás.')
            ->setAuthorEmail('author@example.com');
    }

    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
