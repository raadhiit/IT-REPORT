<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityAttachment>
 */
class ActivityAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->word().'.pdf';

        return [
            'activity_id' => Activity::factory(),
            'path' => 'activity-attachments/'.fake()->uuid().'.pdf',
            'original_name' => $fileName,
            'size' => fake()->numberBetween(1024, 2 * 1024 * 1024),
        ];
    }
}
