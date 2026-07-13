<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Categories\CreateCategoryAction;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CategorySeeder extends Seeder
{
    /**
     * @var list<array{
     *     name: string,
     *     children?: list<array{
     *         name: string,
     *         children?: list<array{
     *             name: string,
     *             children?: list<array{name: string}>
     *         }>
     *     }>
     * }>
     */
    private const array TREE = [
        [
            'name' => 'Motoryzacja',
            'children' => [
                ['name' => 'Samochody'],
                ['name' => 'Motocykle i skutery'],
                [
                    'name' => 'Części i akcesoria',
                    'children' => [
                        ['name' => 'Opony i felgi'],
                        ['name' => 'Oleje i płyny'],
                        ['name' => 'Części karoserii'],
                    ],
                ],
                ['name' => 'Dostawcze i ciężarowe'],
                ['name' => 'Przyczepy i naczepy'],
                ['name' => 'Serwis i naprawa'],
            ],
        ],
        [
            'name' => 'Nieruchomości',
            'children' => [
                ['name' => 'Mieszkania'],
                ['name' => 'Domy'],
                ['name' => 'Działki i grunty'],
                ['name' => 'Lokale i biura'],
                ['name' => 'Garaże i parkingi'],
                ['name' => 'Pokoje i stancje'],
            ],
        ],
        [
            'name' => 'Elektronika',
            'children' => [
                ['name' => 'Telefony'],
                [
                    'name' => 'Komputery',
                    'children' => [
                        ['name' => 'Laptopy'],
                        ['name' => 'Komponenty'],
                        ['name' => 'Peryferia'],
                    ],
                ],
                ['name' => 'RTV i audio'],
                ['name' => 'Aparaty fotograficzne'],
                ['name' => 'Konsole i gry'],
                ['name' => 'Akcesoria elektroniczne'],
            ],
        ],
        [
            'name' => 'Dom i ogród',
            'children' => [
                ['name' => 'Meble'],
                ['name' => 'Ogród'],
                [
                    'name' => 'Oświetlenie',
                    'children' => [
                        ['name' => 'Lampy sufitowe'],
                        ['name' => 'Lampki biurkowe'],
                        ['name' => 'Źródła światła'],
                    ],
                ],
                ['name' => 'AGD'],
                ['name' => 'Narzędzia'],
                ['name' => 'Dekoracje'],
            ],
        ],
        [
            'name' => 'Moda',
            'children' => [
                ['name' => 'Odzież damska'],
                ['name' => 'Odzież męska'],
                ['name' => 'Obuwie'],
                ['name' => 'Biżuteria'],
                ['name' => 'Akcesoria modowe'],
            ],
        ],
        [
            'name' => 'Dla dzieci',
            'children' => [
                ['name' => 'Wózki dziecięce'],
                ['name' => 'Foteliki samochodowe'],
                ['name' => 'Ubranka dziecięce'],
                ['name' => 'Zabawki'],
                ['name' => 'Akcesoria dla dzieci'],
            ],
        ],
        [
            'name' => 'Sport i hobby',
            'children' => [
                ['name' => 'Rowery'],
                ['name' => 'Siłownia i fitness'],
                ['name' => 'Turystyka'],
                ['name' => 'Muzyka i instrumenty'],
                ['name' => 'Kolekcje'],
            ],
        ],
        [
            'name' => 'Praca',
            'children' => [
                ['name' => 'Oferty pracy'],
                ['name' => 'Szukam pracy'],
                ['name' => 'Freelance'],
                ['name' => 'Praktyki i staże'],
            ],
        ],
        [
            'name' => 'Usługi',
            'children' => [
                ['name' => 'Budowlane'],
                ['name' => 'Transportowe'],
                ['name' => 'Sprzątanie'],
                ['name' => 'Nauka i korepetycje'],
                ['name' => 'Naprawy'],
                ['name' => 'Pozostałe usługi'],
            ],
        ],
    ];

    public function run(CreateCategoryAction $createCategory): void
    {
        $this->seedNodes($createCategory, self::TREE);
    }

    /**
     * Idempotent: re-seeding must not duplicate nodes or their closure rows.
     */
    private function firstOrCreate(
        CreateCategoryAction $createCategory,
        string $name,
        ?int $parentId,
        int $position,
    ): Category {
        $slug = Str::slug($name);

        $existing = Category::query()->where('slug', $slug)->first();

        if ($existing !== null) {
            return $existing;
        }

        return $createCategory->execute([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'position' => $position,
            'is_visible' => true,
        ]);
    }

    /**
     * @param  list<array{
     *     name: string,
     *     children?: list<array<string, mixed>>
     * }>  $nodes
     */
    private function seedNodes(CreateCategoryAction $createCategory, array $nodes, ?Category $parent = null): void
    {
        foreach ($nodes as $position => $node) {
            $category = $this->firstOrCreate(
                $createCategory,
                $node['name'],
                $parent?->id,
                $position,
            );

            if (! isset($node['children'])) {
                continue;
            }

            $this->seedNodes($createCategory, $node['children'], $category);
        }
    }
}
