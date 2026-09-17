<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\ElasticSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository    $productRepository,
        private readonly ElasticSearchService $es,
    )
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        if (!empty($q)) {
            $products = $this->es
                ->getClient()->search([
                    'index' => 'products',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    [
                                        'multi_match' => [
                                            'query'     => $q,
                                            'fields'    => ['title^2', 'description'],
                                            'fuzziness' => 'AUTO',
                                        ],
                                    ],
                                    [
                                        'match_phrase_prefix' => [
                                            'title' => [
                                                'query' => $q,
                                                'boost' => 0.5,
                                            ],
                                        ],
                                    ],
                                ],
                                'minimum_should_match' => 1,
                            ],
                        ],
                    ]
                ]);

            $products = array_map(fn($h) => $h['_source'], $products['hits']['hits']);
        } else {
            $products = $this->productRepository->findAll();
        }

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'q' => $q,
        ]);
    }
}
