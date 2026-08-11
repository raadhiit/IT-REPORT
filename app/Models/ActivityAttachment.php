<?php

namespace App\Models;

use Database\Factories\ActivityAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $activity_id
 * @property string $path
 * @property string $original_name
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['activity_id', 'path', 'original_name', 'size'])]
class ActivityAttachment extends Model
{
    /** @use HasFactory<ActivityAttachmentFactory> */
    use HasFactory;

    /**
     * Get the activity this attachment belongs to.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
