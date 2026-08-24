<?php

namespace App\Models;

use App\Enums\WeeklyReportLogStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property WeeklyReportLogStatus $status
 * @property string $recipient_email
 * @property string|null $error_message
 * @property string|null $excel_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'period_start', 'period_end', 'status', 'recipient_email', 'error_message', 'excel_path'])]
class WeeklyReportLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'status' => WeeklyReportLogStatus::class,
        ];
    }

    /**
     * Get the staff member this send attempt was for.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
