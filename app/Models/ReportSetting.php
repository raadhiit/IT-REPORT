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
 * @property string|null $office_mail_host
 * @property int|null $office_mail_port
 * @property 'ssl'|'tls'|null $office_mail_encryption
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['gm_email', 'gm_name', 'spv_email', 'spv_name', 'send_day', 'send_time', 'office_mail_host', 'office_mail_port', 'office_mail_encryption'])]
class ReportSetting extends Model
{
    /**
     * Get the single row of report settings, creating it if it doesn't exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Determine if the configured send day/time matches the given moment (to the minute).
     */
    public function isDueAt(CarbonInterface $now): bool
    {
        return $now->dayOfWeek === $this->send_day && $now->format('H:i') === $this->send_time;
    }
}
