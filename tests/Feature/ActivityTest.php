<?php

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\User;
use App\Services\WeeklyReportAggregator;
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
        'status' => 'selesai',
        'deskripsi' => 'Fixed printer on 3rd floor',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('activities.index'));

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'kategori' => 'support',
        'deskripsi' => 'Fixed printer on 3rd floor',
    ]);
});

test('a project activity can log progress and a target date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'project',
        'status' => 'on_track',
        'deskripsi' => 'Migrasi server file ke NAS baru',
        'progress_percent' => 75,
        'target_selesai' => '2026-09-20',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'progress_percent' => 75,
        'target_selesai' => '2026-09-20',
    ]);
});

test('progress percent over 100 is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'tanggal' => '2026-08-11',
        'kategori' => 'project',
        'status' => 'on_track',
        'deskripsi' => 'Migrasi server file ke NAS baru',
        'progress_percent' => 150,
    ]);

    $response->assertSessionHasErrors('progress_percent');
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
        'status' => 'selesai',
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

test('the activity list defaults to the current week', function () {
    $user = User::factory()->create();
    [$start, $end] = WeeklyReportAggregator::currentWeek();

    $inWeek = Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'deskripsi' => 'In week']);
    Activity::factory()->for($user)->create(['tanggal' => $start->subDay()->toDateString(), 'deskripsi' => 'Last week']);

    $response = $this->actingAs($user)->get(route('activities.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('from', $start->toDateString())
        ->where('to', $end->toDateString())
        ->has('activities', 1)
        ->where('activities.0.id', $inWeek->id)
    );
});

test('the activity list can be filtered by a custom date range', function () {
    $user = User::factory()->create();
    Activity::factory()->for($user)->create(['tanggal' => '2026-07-01', 'deskripsi' => 'July']);
    Activity::factory()->for($user)->create(['tanggal' => '2026-08-01', 'deskripsi' => 'August']);

    $response = $this->actingAs($user)->get(route('activities.index', ['from' => '2026-07-01', 'to' => '2026-07-31']));

    $response->assertInertia(fn ($page) => $page
        ->where('from', '2026-07-01')
        ->where('to', '2026-07-31')
        ->has('activities', 1)
        ->where('activities.0.deskripsi', 'July')
    );
});

test('a user can edit their own activity', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create(['deskripsi' => 'Old description']);

    $response = $this->actingAs($user)->put(route('activities.update', $activity), [
        'tanggal' => '2026-08-12',
        'kategori' => 'project',
        'status' => 'on_track',
        'deskripsi' => 'Updated description',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('activities.index'));

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'kategori' => 'project',
        'deskripsi' => 'Updated description',
    ]);
});

test('a user cannot edit another users activity', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)->put(route('activities.update', $activity), [
        'tanggal' => '2026-08-12',
        'kategori' => 'project',
        'deskripsi' => 'Hijacked',
    ]);

    $response->assertForbidden();
});

test('an admin can edit any users activity', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $activity = Activity::factory()->for($owner)->create();

    $response = $this->actingAs($admin)->put(route('activities.update', $activity), [
        'tanggal' => '2026-08-12',
        'kategori' => 'support',
        'status' => 'selesai',
        'deskripsi' => 'Fixed by admin',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('activities.index'));

    $this->assertDatabaseHas('activities', ['id' => $activity->id, 'deskripsi' => 'Fixed by admin']);
});

test('an invalid date range falls back to the current week instead of erroring', function () {
    $user = User::factory()->create();
    [$start, $end] = WeeklyReportAggregator::currentWeek();

    $response = $this->actingAs($user)->get(route('activities.index', ['from' => 'not-a-date', 'to' => 'also-not-a-date']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('from', $start->toDateString())
        ->where('to', $end->toDateString())
    );
});
