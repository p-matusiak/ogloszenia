<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closure table for the category tree: one row for every ancestor/descendant
 * pair, including each node's zero-depth self reference. "All ads under
 * Motoryzacja" is then one indexed join rather than a recursive query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_closure', function (Blueprint $table): void {
            $table->foreignId('ancestor_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('categories')->cascadeOnDelete();

            // 0 = the node itself, 1 = direct child, and so on.
            $table->unsignedSmallInteger('depth');

            $table->primary(['ancestor_id', 'descendant_id']);

            // Walking upwards (a node's ancestors) needs descendant_id leading.
            $table->index(['descendant_id', 'depth']);
            $table->index(['ancestor_id', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_closure');
    }
};
