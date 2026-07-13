<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\AdReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ad_id
 * @property int|null $reporter_id
 * @property string $reason
 * @property string|null $message
 * @property ReportStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ad $ad
 * @property-read User|null $reporter
 */
#[Fillable(['ad_id', 'reporter_id', 'reason', 'message', 'status'])]
final class AdReport extends Model
{
    /** @use HasFactory<AdReportFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Ad, $this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @param  Builder<AdReport>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ReportStatus::Pending);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
        ];
    }
}
