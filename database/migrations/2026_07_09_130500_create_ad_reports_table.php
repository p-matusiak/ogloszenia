<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();

            // Guests may report, and a reporter's account may later be removed,
            // so the report itself must outlive the reporter.
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reason', 60);
            $table->text('message')->nullable();
            $table->string('status', 20)->default(ReportStatus::Pending->value);
            $table->timestampsTz();

            $table->index(['status', 'created_at']);
            $table->index('ad_id');
        });

        DB::statement(sprintf(
            "ALTER TABLE ad_reports ADD CONSTRAINT ad_reports_status_check CHECK (status IN ('%s'))",
            implode("','", ReportStatus::values()),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_reports');
    }
};
