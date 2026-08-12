<?php

use App\Models\ReportSetting;
use App\Models\User;

test('admin can view monitoring', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.monitoring.index'));

    $response->assertOk();
});

test('staff cannot view monitoring', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->get(route('admin.monitoring.index'));

    $response->assertForbidden();
});

test('it shows the configured schedule and last sent date', function () {
    $admin = User::factory()->admin()->create();
    ReportSetting::current()->update(['send_day' => 5, 'send_time' => '17:00']);

    $response = $this->actingAs($admin)->get(route('admin.monitoring.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('schedule.send_time', '17:00')
        ->where('schedule.last_sent_at', null)
    );
});
