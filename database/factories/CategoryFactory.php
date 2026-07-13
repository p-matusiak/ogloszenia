<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Services\CategoryClosureRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'position' => fake()->numberBetween(0, 50),
            'is_visible' => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_visible' => false,
        ]);
    }

    /**
     * A category is meaningless to the application until its closure rows
     * exist, so the factory writes them for every node it creates.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Category $category): void {
            app(CategoryClosureRepository::class)->insertNode($category->id, $category->parent_id);
        });
    }
}
