<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ElasticSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:es:reindex',
    description: 'Переиндексирует Products из Postgres в Elasticsearch',
)]
class ReindexProductsCommand extends Command
{
    private const string INDEX_NAME = 'products';
    private const int BATCH_SIZE = 500;

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ElasticSearchService $es,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $client = $this->es->getClient();

        $io->section('Пересоздаём индекс');
        $this->recreateIndex($client);
        $io->success('Индекс пересоздан');

        $io->section('Индексируем товары');
        $total = 0;
        $batch = [];

        $query = $this->productRepository
            ->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->getQuery();

        foreach ($query->toIterable() as $product) {
            array_push($batch, ...$this->buildBulkAction($product));
            $total++;

            if (count($batch) >= self::BATCH_SIZE * 2) {
                $this->sendBulk($client, $batch);
                $batch = [];

                $this->em->clear();

                $io->text(sprintf('Обработано: %d', $total));
            }
        }

        // остаток
        if (!empty($batch)) {
            $this->sendBulk($client, $batch);
        }

        $client->indices()->refresh(['index' => self::INDEX_NAME]);

        $io->success(sprintf('Проиндексировано %d товаров', $total));

        return Command::SUCCESS;
    }

    private function recreateIndex($client): void
    {
        try {
            $client->indices()->delete(['index' => self::INDEX_NAME]);
        } catch (Throwable) {

        }

        $client->indices()->create([
            'index' => self::INDEX_NAME,
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'title'       => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'price'       => ['type' => 'float'],
                        'image'       => ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);
    }

    private function buildBulkAction(Product $product): array
    {
        return [
            ['index' => ['_index' => self::INDEX_NAME, '_id' => $product->getId()]],
            [
                'title'       => $product->getTitle(),
                'description' => $product->getDescription(),
                'price'       => $product->getPrice(),
                'image'       => $product->getImage(),
            ],
        ];
    }

    private function sendBulk($client, array $batch): void
    {
        $response = $client->bulk(['body' => $batch]);

        if (!empty($response['errors'])) {
            foreach ($response['items'] as $item) {
                if (isset($item['index']['error'])) {
                    $this->logger->error($item['index']['error']);
                }
            }
        }
    }
}
