<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Repositories\Contracts\CategoryRepository;
use App\Services\Contracts\AdCategorySuggester;
use App\Support\CategoryLeafCatalog;
use App\Support\CategoryTreeBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAiAdCategorySuggester implements AdCategorySuggester
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly CategoryTreeBuilder $treeBuilder,
        private readonly CategoryLeafCatalog $leafCatalog,
    ) {}

    public function isAvailable(): bool
    {
        return Config::boolean('ai.enabled') && filled(Config::get('ai.openai_api_key'));
    }

    public function suggestForTitle(string $title): ?int
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $title = trim($title);

        if ($title === '') {
            return null;
        }

        $leaves = $this->leafCatalog->leavesFromRoots(
            $this->treeBuilder->build($this->categories->listVisibleOrdered()),
        );

        if ($leaves === []) {
            return null;
        }

        try {
            $response = Http::withToken((string) Config::get('ai.openai_api_key'))
                ->timeout(Config::integer('ai.timeout_seconds'))
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => Config::string('ai.category_model'),
                    'temperature' => 0,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'category_suggestion',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'category_id' => [
                                        'type' => ['integer', 'null'],
                                    ],
                                ],
                                'required' => ['category_id'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Wybierz najlepiej pasującą kategorię liścia dla polskiego ogłoszenia. Zwróć wyłącznie category_id z listy albo null, gdy brak sensownego dopasowania.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Tytuł ogłoszenia:\n{$title}\n\nDozwolone kategorie (id: ścieżka):\n".$this->formatLeaves($leaves),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI category suggestion failed.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || $content === '') {
                return null;
            }

            /** @var array{category_id?: int|null} $decoded */
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
            $categoryId = $decoded['category_id'] ?? null;

            if (! is_int($categoryId)) {
                return null;
            }

            return $this->categories->isVisibleLeaf($categoryId) ? $categoryId : null;
        } catch (\Throwable $exception) {
            Log::warning('OpenAI category suggestion unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  list<array{id: int, path: string}>  $leaves
     */
    private function formatLeaves(array $leaves): string
    {
        return implode("\n", array_map(
            static fn (array $leaf): string => $leaf['id'].': '.$leaf['path'],
            $leaves,
        ));
    }
}
