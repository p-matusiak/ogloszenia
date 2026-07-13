<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // An ad hangs off exactly one node, always a leaf ("Samochody").
            // Its parent ("Motoryzacja") is reached through category_closure,
            // so the category and subcategory are never stored twice.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('title', 150);
            $table->string('slug', 200)->unique();
            $table->text('description');

            $table->decimal('price', 12, 2)->nullable();
            $table->string('location', 120)->nullable();

            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();

            $table->string('status', 20)->default(AdStatus::Pending->value);
            $table->text('rejection_reason')->nullable();

            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('terms_accepted_at');

            $table->unsignedInteger('views_count')->default(0);

            $table->timestampsTz();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
        });

        DB::statement(sprintf(
            "ALTER TABLE ads ADD CONSTRAINT ads_status_check CHECK (status IN ('%s'))",
            implode("','", AdStatus::values()),
        ));

        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_price_non_negative CHECK (price IS NULL OR price >= 0)');

        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_contact_present CHECK (contact_email IS NOT NULL OR contact_phone IS NOT NULL)');

        // Maintained by Postgres, so title/description edits can never leave a
        // stale search index behind. The two-argument to_tsvector is IMMUTABLE,
        // which a STORED generated column requires.
        DB::statement("
            ALTER TABLE ads ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector('simple'::regconfig, coalesce(title, '') || ' ' || coalesce(description, ''))
            ) STORED
        ");

        DB::statement('CREATE INDEX ads_search_vector_index ON ads USING GIN (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
