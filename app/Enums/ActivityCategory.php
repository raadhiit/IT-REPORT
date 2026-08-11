<?php

namespace App\Enums;

enum ActivityCategory: string
{
    case Maintenance = 'maintenance';
    case Project = 'project';
    case Support = 'support';
    case Meeting = 'meeting';
    case Other = 'other';

    /**
     * Get the human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::Project => 'Project',
            self::Support => 'Support / Troubleshooting',
            self::Meeting => 'Meeting',
            self::Other => 'Lainnya',
        };
    }
}
