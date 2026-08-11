<?php

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\User;

test('an activity belongs to a user and casts its category', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create(['kategori' => ActivityCategory::Support]);

    expect($activity->user)->toBeInstanceOf(User::class);
    expect($activity->user->id)->toBe($user->id);
    expect($activity->kategori)->toBe(ActivityCategory::Support);
    expect($user->activities)->toHaveCount(1);
});

test('an activity can have many attachments', function () {
    $activity = Activity::factory()->create();
    ActivityAttachment::factory()->for($activity)->count(2)->create();

    expect($activity->attachments)->toHaveCount(2);
    expect($activity->attachments->first())->toBeInstanceOf(ActivityAttachment::class);
    expect($activity->attachments->first()->activity->id)->toBe($activity->id);
});

test('deleting an activity deletes its attachments', function () {
    $activity = Activity::factory()->create();
    $attachment = ActivityAttachment::factory()->for($activity)->create();

    $activity->delete();

    expect(ActivityAttachment::find($attachment->id))->toBeNull();
});
