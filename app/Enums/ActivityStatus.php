<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Selesai = 'selesai';
    case OnTrack = 'on_track';
    case Pending = 'pending';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai',
            self::OnTrack => 'On Track',
            self::Pending => 'Pending',
        };
    }
}
