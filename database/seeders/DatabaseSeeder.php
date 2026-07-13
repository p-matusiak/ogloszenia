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
        $this->call(AdSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@ogloszenia.local'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'is_admin' => true,
            ],
        );
    }
}
