<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ad;
use App\Models\AdImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdImage>
 */
final class AdImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'disk' => 'public',
            'path' => 'ads/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'size_bytes' => fake()->numberBetween(20_000, 2_000_000),
            'position' => AdImage::PRIMARY_POSITION,
        ];
    }
}
