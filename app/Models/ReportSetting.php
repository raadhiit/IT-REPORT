<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $gm_email
 * @property string|null $gm_name
 * @property string|null $spv_email
 * @property string|null $spv_name
 * @property int $send_day Carbon dayOfWeek: 0=Sunday..6=Saturday
 * @property string $send_time HH:mm
 * @property Carbon|null $last_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['gm_email', 'gm_name', 'spv_email', 'spv_name', 'send_day', 'send_time'])]
class ReportSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_sent_at' => 'date',
        ];
    }

    /**
     * Get the single row of report settings, creating it if it doesn't exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Determine if the configured send day/time has arrived and it hasn't already been
     * sent today. Uses a "time has passed" window rather than an exact-minute match,
     * since some shared hosting plans throttle cron to run less often than every minute.
     */
    public function isDueAt(CarbonInterface $now): bool
    {
        if ($now->dayOfWeek !== $this->send_day || $now->format('H:i') < $this->send_time) {
            return false;
        }

        return $this->last_sent_at === null || ! $this->last_sent_at->isSameDay($now);
    }

    /**
     * Mark the report as sent for today, so isDueAt() won't fire again until next week.
     */
    public function markSentNow(): void
    {
        $this->forceFill(['last_sent_at' => now()])->save();
    }
}
