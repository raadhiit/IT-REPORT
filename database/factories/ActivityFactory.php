<?php

namespace Database\Factories;

use App\Enums\ActivityCategory;
use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'kategori' => fake()->randomElement(ActivityCategory::cases()),
            'deskripsi' => fake()->sentence(),
            'status' => fake()->randomElement(ActivityStatus::cases()),
        ];
    }

    /**
     * A project activity with progress tracking filled in.
     */
    public function project(): static
    {
        return $this->state(fn (): array => [
            'kategori' => ActivityCategory::Project,
            'progress_percent' => fake()->numberBetween(0, 100),
            'target_selesai' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
        ]);
    }
}
