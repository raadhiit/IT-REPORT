<?php

use App\Mail\WeeklyReportMail;
use App\Models\Activity;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function configureGmSettings(array $overrides = []): ReportSetting
{
    return tap(ReportSetting::current())->update([
        'gm_name' => 'Rendra',
        'gm_email' => 'gm@example.com',
        ...$overrides,
    ]);
}

function staffWithOfficeMailbox(array $attributes = []): User
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

test('it sends one email per staff with an office mailbox configured, from that staff', function () {
    Mail::fake();

    configureGmSettings(['spv_name' => 'Ryan', 'spv_email' => 'spv@example.com']);

    $radhit = staffWithOfficeMailbox(['name' => 'Radhit']);
    $budi = staffWithOfficeMailbox(['name' => 'Budi', 'office_mail_host' => 'mail.other.com']);
    Activity::factory()->for($radhit)->create();
    Activity::factory()->for($budi)->create();

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('gm@example.com')
        && $mail->hasCc('spv@example.com')
        && $mail->hasFrom($radhit->office_email));

    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('gm@example.com')
        && $mail->hasFrom($budi->office_email));

    Mail::assertSentCount(2);
});

test('it sends without cc when no spv email is configured', function () {
    Mail::fake();

    configureGmSettings();
    staffWithOfficeMailbox();

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('gm@example.com'));
});

test('it skips sending when no gm email is configured', function () {
    Mail::fake();

    staffWithOfficeMailbox();

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it skips sending when gm email is set but gm name is missing', function () {
    Mail::fake();

    ReportSetting::current()->update(['gm_email' => 'gm@example.com', 'gm_name' => null]);
    staffWithOfficeMailbox();

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it skips gracefully when no active staff has an office mailbox configured', function () {
    Mail::fake();

    configureGmSettings();
    User::factory()->create(['office_email' => null, 'office_email_password' => null]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it excludes staff whose office mailbox is missing SMTP host/port/encryption', function () {
    Mail::fake();

    configureGmSettings();
    staffWithOfficeMailbox(['office_mail_host' => null]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});

test('it excludes inactive staff even if they have an office mailbox configured', function () {
    Mail::fake();

    configureGmSettings();
    staffWithOfficeMailbox(['is_active' => false]);

    $this->artisan('report:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
});
