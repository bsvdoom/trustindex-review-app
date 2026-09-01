<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReviewControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testValidReviewSubmissionPersistsRedirectsAndDisplaysFlash(): void
    {
        $crawler = $this->client->request('GET', '/reviews/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Vélemény elküldése')->form([
            'review[companyName]' => 'Functional Company',
            'review[rating]' => '5',
            'review[reviewText]' => 'Funkcionális tesztvélemény.',
            'review[authorEmail]' => 'functional@example.com',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.flash-success', 'Köszönjük a véleményed!');
        self::assertSelectorTextContains('.review-list', 'Functional Company');
        self::assertSelectorTextContains('.review-list', 'Funkcionális tesztvélemény.');

        $reviews = $this->repository()->findByCompanyNameOrderedByNewest('Functional Company');
        self::assertCount(1, $reviews);
        self::assertSame('functional@example.com', $reviews[0]->getAuthorEmail());
    }

    public function testSubmissionUsesTheCanonicalCompanyNameSpelling(): void
    {
        $this->persistReview('Acme Kft.', 5, 'Első vélemény.');

        $crawler = $this->client->request('GET', '/reviews/new');
        $form = $crawler->selectButton('Vélemény elküldése')->form([
            'review[companyName]' => 'ACME KFT.',
            'review[rating]' => '4',
            'review[reviewText]' => 'Második vélemény.',
            'review[authorEmail]' => 'second@example.com',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/');
        $reviews = $this->repository()->findByCompanyNameOrderedByNewest('acme kft.');
        self::assertCount(2, $reviews);
        self::assertSame(['Acme Kft.', 'Acme Kft.'], array_map(
            static fn (Review $review): ?string => $review->getCompanyName(),
            $reviews,
        ));
    }

    public function testInvalidFormDisplaysErrorsAndDoesNotPersistReview(): void
    {
        $crawler = $this->client->request('GET', '/reviews/new');
        $form = $crawler->selectButton('Vélemény elküldése')->form([
            'review[companyName]' => 'Invalid Company',
            'review[rating]' => '3',
            'review[reviewText]' => '',
            'review[authorEmail]' => 'hibás-email',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-card', 'A vélemény szövege kötelező.');
        self::assertSelectorTextContains('.form-card', 'Adj meg érvényes e-mail-címet.');
        self::assertCount(0, $this->repository()->findByCompanyNameOrderedByNewest('Invalid Company'));
    }

    public function testPublicReviewAndCompanyPagesReturnExpectedStatuses(): void
    {
        $review = $this->persistReview('Public Company', 4, 'Nyilvános vélemény.');
        $router = self::getContainer()->get(UrlGeneratorInterface::class);

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', $router->generate('app_review_show', ['id' => $review->getId()]));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/reviews/999999999');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/companies');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', $router->generate('app_company_show', ['name' => 'Public Company']));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', $router->generate('app_company_show', ['name' => 'Nem létező cég']));
        self::assertResponseStatusCodeSame(404);
    }

    private function persistReview(string $companyName, int $rating, string $reviewText): Review
    {
        $review = (new Review())
            ->setCompanyName($companyName)
            ->setRating($rating)
            ->setReviewText($reviewText)
            ->setAuthorEmail('seed@example.com');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($review);
        $entityManager->flush();

        return $review;
    }

    private function repository(): ReviewRepository
    {
        $repository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Review::class);
        self::assertInstanceOf(ReviewRepository::class, $repository);

        return $repository;
    }
}
