<?php

use App\Models\ReportSetting;
use Carbon\CarbonImmutable;

test('isDueAt is true once the configured day/time has arrived', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->fresh()->isDueAt(CarbonImmutable::parse('2026-08-14 17:00')))->toBeTrue(); // Friday
});

test('isDueAt stays true for later ticks the same day (cron may not fire every minute)', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->fresh()->isDueAt(CarbonImmutable::parse('2026-08-14 17:06')))->toBeTrue(); // Friday, 6 minutes late
});

test('isDueAt is false on the wrong day', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->isDueAt(CarbonImmutable::parse('2026-08-13 17:00')))->toBeFalse(); // Thursday
});

test('isDueAt is false before the configured time', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->isDueAt(CarbonImmutable::parse('2026-08-14 16:59')))->toBeFalse();
});

test('isDueAt is false once already sent today', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);
    $setting->forceFill(['last_sent_at' => CarbonImmutable::parse('2026-08-14 17:03')])->save();

    expect($setting->fresh()->isDueAt(CarbonImmutable::parse('2026-08-14 17:06')))->toBeFalse(); // same Friday, later tick
});

test('markSentNow records today so isDueAt flips false immediately after', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => CarbonImmutable::now()->dayOfWeek, 'send_time' => '00:00']);

    expect($setting->fresh()->isDueAt(CarbonImmutable::now()))->toBeTrue();

    $setting->markSentNow();

    expect($setting->fresh()->isDueAt(CarbonImmutable::now()))->toBeFalse();
});

test('isDueAt is true again the following week after being sent', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);
    $setting->forceFill(['last_sent_at' => CarbonImmutable::parse('2026-08-14')])->save();

    expect($setting->fresh()->isDueAt(CarbonImmutable::parse('2026-08-21 17:00')))->toBeTrue(); // next Friday
});
