<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdCondition;
use App\Enums\AdStatus;
use App\Enums\DeliveryMethod;
use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ad>
 */
final class AdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->unique()->words(4, true));

        return [
            'user_id' => User::factory(),
            // A leaf node under a root, mirroring "Motoryzacja > Samochody".
            'category_id' => fn (): int => Category::factory()
                ->childOf(Category::factory()->create())
                ->create()
                ->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->boolean(70) ? fake()->randomFloat(2, 10, 50_000) : null,
            'is_negotiable' => fake()->boolean(40),
            'condition' => fake()->randomElement(AdCondition::cases()),
            'delivery_methods' => fake()->randomElements(
                DeliveryMethod::values(),
                fake()->numberBetween(1, 3),
            ),
            'location' => fake()->boolean(80) ? fake()->city() : null,
            'contact_email' => null,
            'contact_phone' => null,
            'status' => AdStatus::Active,
            'published_at' => now(),
            'expires_at' => now()->copy()->addDays((int) config('ads.lifetime_days')),
            'terms_accepted_at' => now(),
            'views_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Pending,
            'published_at' => null,
            'expires_at' => null,
        ]);
    }

    public function rejected(string $reason = 'Narusza regulamin.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Rejected,
            'rejection_reason' => $reason,
            'published_at' => null,
            'expires_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Expired,
            'published_at' => now()->copy()->subDays(45),
            'expires_at' => now()->copy()->subDays(15),
        ]);
    }

    /**
     * Wygasłe, ale jeszcze w okresie na odświeżenie (domyślnie 30 dni po expires_at).
     */
    public function expiredWithinRefreshGrace(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Expired,
            'published_at' => now()->copy()->subDays(45),
            'expires_at' => now()->copy()->subDays(15),
        ]);
    }

    /**
     * Wygasłe po upływie okresu na odświeżenie — kwalifikuje się do usunięcia.
     */
    public function expiredPastRefreshGrace(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Expired,
            'published_at' => now()->copy()->subDays(90),
            'expires_at' => now()->copy()->subDays(60),
        ]);
    }

    /**
     * Wygasłe, dla którego należy wysłać ostrzeżenie przed usunięciem.
     */
    public function expiredDueForDeletionWarning(): static
    {
        $graceDays = (int) config('ads.refresh_grace_days');
        $warningDays = (int) config('ads.deletion_warning_days');

        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Expired,
            'published_at' => now()->copy()->subDays($graceDays + 35),
            'expires_at' => now()->copy()->subDays($graceDays - $warningDays + 1),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Deleted,
        ]);
    }

    /**
     * Active but already past its expiry date: what the expiry sweep looks for,
     * and the only state a refresh is allowed from.
     */
    public function lapsed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdStatus::Active,
            'published_at' => now()->copy()->subDays(31),
            'expires_at' => now()->copy()->subDay(),
        ]);
    }

    public function in(Category $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $category->id,
        ]);
    }
}
