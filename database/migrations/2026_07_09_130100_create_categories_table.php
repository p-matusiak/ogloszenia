<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();

            // Adjacency pointer for the immediate parent. Ancestor queries go
            // through category_closure, never through recursive parent walks.
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();

            $table->string('name', 100);

            // Globally unique so a category resolves from /kategoria/{slug}
            // without knowing its depth.
            $table->string('slug', 120)->unique();

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestampsTz();

            $table->index(['parent_id', 'is_visible', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
