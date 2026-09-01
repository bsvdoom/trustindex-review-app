<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/', name: 'app_review_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $query = mb_substr($query, 0, 255);
        $query = '' === $query ? null : $query;

        return $this->render('review/index.html.twig', [
            'reviews' => $reviewRepository->findAllOrderedByNewest($query),
            'query' => $query,
        ]);
    }

    #[Route('/reviews/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ReviewRepository $reviewRepository,
    ): Response {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $companyName = $review->getCompanyName();

            if (null !== $companyName) {
                $canonicalCompanyName = $reviewRepository->findCanonicalCompanyName($companyName);

                if (null !== $canonicalCompanyName) {
                    $review->setCompanyName($canonicalCompanyName);
                }
            }

            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Köszönjük a véleményed!');

            return $this->redirectToRoute('app_review_index');
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
            'companyNames' => $reviewRepository->findCompanyNameSuggestions(),
        ]);
    }

    #[Route('/reviews/{id}', name: 'app_review_show', requirements: ['id' => '[1-9][0-9]*'], methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }
}
