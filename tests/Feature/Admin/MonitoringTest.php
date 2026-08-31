<?php

use App\Enums\WeeklyReportLogStatus;
use App\Mail\WeeklyReportMail;
use App\Models\ReportSetting;
use App\Models\User;
use App\Models\WeeklyReportLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function monitoringStaffWithOfficeMailbox(array $attributes = []): User
{
    return User::factory()->create([
        'office_email' => fake()->unique()->safeEmail(),
        'office_email_password' => 'secret',
        'office_mail_host' => 'mail.example.com',
        'office_mail_port' => 465,
        'office_mail_encryption' => 'ssl',
        ...$attributes,
    ]);
}

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

test('it lists weekly report send attempts with their status', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create(['name' => 'Radhit']);

    WeeklyReportLog::create([
        'user_id' => $staff->id,
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-16',
        'status' => WeeklyReportLogStatus::Sent,
        'recipient_email' => 'gm@example.com',
        'excel_path' => 'weekly-report-logs/1.xlsx',
    ]);
    WeeklyReportLog::create([
        'user_id' => $staff->id,
        'period_start' => '2026-08-03',
        'period_end' => '2026-08-09',
        'status' => WeeklyReportLogStatus::Failed,
        'recipient_email' => 'gm@example.com',
        'error_message' => 'Connection refused',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.monitoring.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('reportLogs', 2)
        ->where('reportLogs.0.status', 'failed')
        ->where('reportLogs.0.error_message', 'Connection refused')
        ->where('reportLogs.0.has_excel', false)
        ->where('reportLogs.1.status', 'sent')
        ->where('reportLogs.1.has_excel', true)
    );
});

test('admin can download the archived excel for a sent log', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create(['name' => 'Radhit']);
    Storage::disk('local')->put('weekly-report-logs/1.xlsx', 'fake-excel-contents');

    $log = WeeklyReportLog::create([
        'user_id' => $staff->id,
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-16',
        'status' => WeeklyReportLogStatus::Sent,
        'recipient_email' => 'gm@example.com',
        'excel_path' => 'weekly-report-logs/1.xlsx',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.monitoring.report-logs.excel', $log));

    $response->assertOk();
});

test('downloading excel for a log without an archived file 404s', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create();

    $log = WeeklyReportLog::create([
        'user_id' => $staff->id,
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-16',
        'status' => WeeklyReportLogStatus::Failed,
        'recipient_email' => 'gm@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.monitoring.report-logs.excel', $log));

    $response->assertNotFound();
});

test('admin can manually trigger the weekly report send for all eligible staff', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com']);
    $radhit = monitoringStaffWithOfficeMailbox(['name' => 'Radhit']);
    $budi = monitoringStaffWithOfficeMailbox(['name' => 'Budi']);

    $response = $this->actingAs($admin)->post(route('admin.monitoring.send-manual'), [
        'from' => '2026-08-24',
        'to' => '2026-08-30',
    ]);

    $response->assertRedirect();
    Mail::assertSentCount(2);
    expect(WeeklyReportLog::where('user_id', $radhit->id)->exists())->toBeTrue();
    expect(WeeklyReportLog::where('user_id', $budi->id)->exists())->toBeTrue();
});

test('an explicit empty user_ids array (the "all staff" default sent by the UI) still sends to everyone', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com']);
    monitoringStaffWithOfficeMailbox(['name' => 'Radhit']);
    monitoringStaffWithOfficeMailbox(['name' => 'Budi']);

    $response = $this->actingAs($admin)->post(route('admin.monitoring.send-manual'), [
        'user_ids' => [],
        'from' => '2026-08-24',
        'to' => '2026-08-30',
    ]);

    $response->assertRedirect();
    Mail::assertSentCount(2);
});

test('admin can manually trigger the weekly report send for a single staff member', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com']);
    $radhit = monitoringStaffWithOfficeMailbox(['name' => 'Radhit']);
    monitoringStaffWithOfficeMailbox(['name' => 'Budi']);

    $response = $this->actingAs($admin)->post(route('admin.monitoring.send-manual'), [
        'user_ids' => [$radhit->id],
        'from' => '2026-08-24',
        'to' => '2026-08-30',
    ]);

    $response->assertRedirect();
    Mail::assertSentCount(1);
    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasFrom($radhit->office_email));
});

test('manual send uses the requested date range instead of the current week', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com']);
    $radhit = monitoringStaffWithOfficeMailbox(['name' => 'Radhit']);

    $this->actingAs($admin)->post(route('admin.monitoring.send-manual'), [
        'user_ids' => [$radhit->id],
        'from' => '2026-07-01',
        'to' => '2026-07-07',
    ]);

    $log = WeeklyReportLog::where('user_id', $radhit->id)->first();
    expect($log->period_start->toDateString())->toBe('2026-07-01');
    expect($log->period_end->toDateString())->toBe('2026-07-07');
});

test('manual send validates the date range', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.monitoring.send-manual'), [
        'from' => '2026-08-30',
        'to' => '2026-08-24',
    ]);

    $response->assertInvalid('to');
});

test('staff cannot manually trigger the weekly report send', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->post(route('admin.monitoring.send-manual'), [
        'from' => '2026-08-24',
        'to' => '2026-08-30',
    ]);

    $response->assertForbidden();
});

test('staff cannot download an archived excel', function () {
    Storage::fake('local');
    $staff = User::factory()->create();
    Storage::disk('local')->put('weekly-report-logs/1.xlsx', 'fake-excel-contents');

    $log = WeeklyReportLog::create([
        'user_id' => $staff->id,
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-16',
        'status' => WeeklyReportLogStatus::Sent,
        'recipient_email' => 'gm@example.com',
        'excel_path' => 'weekly-report-logs/1.xlsx',
    ]);

    $response = $this->actingAs($staff)->get(route('admin.monitoring.report-logs.excel', $log));

    $response->assertForbidden();
});
