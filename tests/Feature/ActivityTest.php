<?php

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('activity log page is displayed', function () {
    $user = User::factory()->create();
    Activity::factory()->for($user)->create();

    $response = $this->actingAs($user)->get(route('activities.index'));

    $response->assertOk();
});

test('a user can log an activity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'support',
        'deskripsi' => 'Fixed printer on 3rd floor',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('activities.index'));

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'kategori' => 'support',
        'deskripsi' => 'Fixed printer on 3rd floor',
    ]);
});

test('activity requires a valid category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'not-a-real-category',
        'deskripsi' => 'Something',
    ]);

    $response->assertSessionHasErrors('kategori');
});

test('a user can upload an attachment with an activity', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'maintenance',
        'deskripsi' => 'Server maintenance',
        'attachments' => [UploadedFile::fake()->create('report.pdf', 500, 'application/pdf')],
    ]);

    $response->assertSessionHasNoErrors();

    $activity = Activity::first();
    expect($activity->attachments)->toHaveCount(1);

    $attachment = $activity->attachments->first();
    Storage::disk('local')->assertExists($attachment->path);
});

test('attachment uploads are rejected when too large or the wrong type', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'maintenance',
        'deskripsi' => 'Server maintenance',
        'attachments' => [UploadedFile::fake()->create('too-big.pdf', 3000, 'application/pdf')],
    ]);

    $response->assertSessionHasErrors('attachments.0');

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'maintenance',
        'deskripsi' => 'Server maintenance',
        'attachments' => [UploadedFile::fake()->create('script.exe', 100)],
    ]);

    $response->assertSessionHasErrors('attachments.0');
});

test('a user can download their own attachment', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    $attachment = ActivityAttachment::factory()->for($activity)->create(['path' => 'activity-attachments/1/report.pdf']);
    Storage::disk('local')->put($attachment->path, 'contents');

    $response = $this->actingAs($user)->get(route('activity-attachments.show', $attachment));

    $response->assertOk();
});

test('a user cannot download another users attachment', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->for($owner)->create();
    $attachment = ActivityAttachment::factory()->for($activity)->create();
    Storage::disk('local')->put($attachment->path, 'contents');

    $response = $this->actingAs($otherUser)->get(route('activity-attachments.show', $attachment));

    $response->assertForbidden();
});

test('an admin can download any users attachment', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $activity = Activity::factory()->for($owner)->create();
    $attachment = ActivityAttachment::factory()->for($activity)->create();
    Storage::disk('local')->put($attachment->path, 'contents');

    $response = $this->actingAs($admin)->get(route('activity-attachments.show', $attachment));

    $response->assertOk();
});
