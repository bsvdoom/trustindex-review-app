<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'company_name', type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'A cégnév megadása kötelező.')]
    #[Assert\Length(max: 255, maxMessage: 'A cégnév legfeljebb {{ limit }} karakter lehet.')]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Assert\NotNull(message: 'Az értékelés megadása kötelező.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Az értékelés 1 és 5 között lehet.')]
    private ?int $rating = null;

    #[ORM\Column(name: 'review_text', type: Types::TEXT, nullable: false)]
    #[Assert\NotBlank(message: 'A vélemény szövege kötelező.')]
    private ?string $reviewText = null;

    #[ORM\Column(name: 'author_email', type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Az e-mail-cím megadása kötelező.')]
    #[Assert\Email(message: 'Adj meg érvényes e-mail-címet.')]
    #[Assert\Length(max: 255, maxMessage: 'Az e-mail-cím legfeljebb {{ limit }} karakter lehet.')]
    private ?string $authorEmail = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = trim($companyName);

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getReviewText(): ?string
    {
        return $this->reviewText;
    }

    public function setReviewText(string $reviewText): self
    {
        $this->reviewText = trim($reviewText);

        return $this;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): self
    {
        $this->authorEmail = trim($authorEmail);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
