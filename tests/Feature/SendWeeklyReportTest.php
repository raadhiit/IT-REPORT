<?php

use App\Mail\WeeklyReportMail;
use App\Models\Activity;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('it emails the gm and cc the spv when both are configured', function () {
    Mail::fake();

    User::factory()->admin()->create();
    $staff = User::factory()->create();
    Activity::factory()->for($staff)->create();

    ReportSetting::current()->update([
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        'spv_name' => 'Ryan',
        'spv_email' => 'spv@example.com',
    ]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyReportMail::class, function (WeeklyReportMail $mail) {
        return $mail->hasTo('gm@example.com') && $mail->hasCc('spv@example.com');
    });
});

test('it sends without cc when no spv email is configured', function () {
    Mail::fake();

    User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com', 'spv_email' => null]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('gm@example.com'));
});

test('it skips sending when no gm email is configured', function () {
    Mail::fake();

    User::factory()->admin()->create();

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it skips sending when gm email is set but gm name is missing', function () {
    Mail::fake();

    User::factory()->admin()->create();
    ReportSetting::current()->update(['gm_email' => 'gm@example.com', 'gm_name' => null]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it fails gracefully when there is no admin user', function () {
    Mail::fake();

    ReportSetting::current()->update(['gm_name' => 'Rendra', 'gm_email' => 'gm@example.com']);

    $this->artisan('report:send-weekly')->assertFailed();

    Mail::assertNothingSent();
});
