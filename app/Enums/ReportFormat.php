<?php

namespace App\Enums;

enum ReportFormat: string
{
    case Excel = 'excel';
    case Pdf = 'pdf';

    /**
     * Get the human-readable label for the format.
     */
    public function label(): string
    {
        return match ($this) {
            self::Excel => 'Excel',
            self::Pdf => 'PDF',
        };
    }
}
