<?php

namespace App\Enums;

enum WeeklyReportLogStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Terkirim',
            self::Failed => 'Gagal',
            self::Skipped => 'Dilewati',
        };
    }
}
