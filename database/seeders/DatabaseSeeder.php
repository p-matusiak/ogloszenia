<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        if (config('seeding.demo_ads_per_category', 0) > 0) {
            $this->call(DemoMarketplaceSeeder::class);
        } else {
            $this->call(AdSeeder::class);
            $this->call(DemoSellerSeeder::class);
        }

        $adminAttributes = [
            'name' => 'Administrator',
            'password' => 'password',
            'is_admin' => true,
        ];

        if (! User::query()->where('email', 'admin@zunto.local')->exists()) {
            $adminAttributes['slug'] = 'administrator';
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@zunto.local'],
            $adminAttributes,
        );
    }
}
