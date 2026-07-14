<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SellerSlugGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_slug_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 120)->unique();
            $table->timestampsTz();

            $table->index('user_id');
        });

        $generator = app(SellerSlugGenerator::class);

        User::withoutGlobalScopes()
            ->whereNull('slug')
            ->orderBy('id')
            ->each(function (User $user) use ($generator): void {
                $user->forceFill([
                    'slug' => $generator->generate($user->name, $user->id),
                ])->saveQuietly();
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable()->change();
        });

        Schema::dropIfExists('user_slug_histories');
    }
};
