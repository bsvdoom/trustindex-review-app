<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompanyController extends AbstractController
{
    #[Route('/companies', name: 'app_company_index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        return $this->render('company/index.html.twig', [
            'statistics' => $reviewRepository->findCompanyStatistics(),
        ]);
    }

    #[Route('/companies/show', name: 'app_company_show', methods: ['GET'])]
    public function show(Request $request, ReviewRepository $reviewRepository): Response
    {
        $companyName = trim((string) $request->query->get('name', ''));

        if ('' === $companyName) {
            return $this->redirectToRoute('app_company_index');
        }

        if (mb_strlen($companyName) > 255) {
            throw $this->createNotFoundException('A cég nem található.');
        }

        $statistics = $reviewRepository->findCompanyStatisticsByName($companyName);

        if (null === $statistics) {
            throw $this->createNotFoundException('A cég nem található.');
        }

        return $this->render('company/show.html.twig', [
            'statistics' => $statistics,
            'reviews' => $reviewRepository->findByCompanyNameOrderedByNewest($companyName),
        ]);
    }
}
