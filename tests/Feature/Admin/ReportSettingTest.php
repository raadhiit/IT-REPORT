<?php

use App\Models\ReportSetting;
use App\Models\User;

test('admin can view report settings', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.report-settings.edit'));

    $response->assertOk();
});

test('staff cannot view report settings', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->get(route('admin.report-settings.edit'));

    $response->assertForbidden();
});

test('admin can update the gm and spv name, email, and schedule', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'spv_name' => 'Ryan',
        'spv_email' => 'spv@example.com',
        'send_day' => 1,
        'send_time' => '09:30',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.report-settings.edit'));

    $setting = ReportSetting::current();
    expect($setting->gm_name)->toBe('Rendra');
    expect($setting->gm_email)->toBe('gm@example.com');
    expect($setting->spv_name)->toBe('Ryan');
    expect($setting->spv_email)->toBe('spv@example.com');
    expect($setting->send_day)->toBe(1);
    expect($setting->send_time)->toBe('09:30');
});

test('spv name and email are optional', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'spv_name' => '',
        'spv_email' => '',
        'send_day' => 5,
        'send_time' => '17:00',
    ]);

    $response->assertSessionHasNoErrors();
    expect(ReportSetting::current()->spv_name)->toBeNull();
    expect(ReportSetting::current()->spv_email)->toBeNull();
});

test('spv name is required when spv email is set', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'spv_email' => 'spv@example.com',
        'send_day' => 5,
        'send_time' => '17:00',
    ]);

    $response->assertSessionHasErrors('spv_name');
});

test('gm email must be a valid email', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'not-an-email',
        'send_day' => 5,
        'send_time' => '17:00',
    ]);

    $response->assertSessionHasErrors('gm_email');
});

test('send_time must be a valid HH:mm value', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'send_day' => 5,
        'send_time' => 'not-a-time',
    ]);

    $response->assertSessionHasErrors('send_time');
});

test('send_day must be between 0 and 6', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.report-settings.update'), [
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'send_day' => 7,
        'send_time' => '17:00',
    ]);

    $response->assertSessionHasErrors('send_day');
});
