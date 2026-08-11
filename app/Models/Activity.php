<?php

namespace App\Models;

use App\Enums\ActivityCategory;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $tanggal
 * @property ActivityCategory $kategori
 * @property string $deskripsi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'tanggal', 'kategori', 'deskripsi'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kategori' => ActivityCategory::class,
        ];
    }

    /**
     * Get the staff member who logged this activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachments for this activity.
     *
     * @return HasMany<ActivityAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class);
    }
}
