<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Ad;
use App\Models\AdReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdReport>
 */
final class AdReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(['spam', 'scam', 'offensive', 'wrong_category', 'other']),
            'message' => fake()->boolean(50) ? fake()->sentence() : null,
            'status' => ReportStatus::Pending,
        ];
    }

    public function fromGuest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reporter_id' => null,
        ]);
    }
}
