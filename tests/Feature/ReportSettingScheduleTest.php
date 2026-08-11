<?php

use App\Models\ReportSetting;
use Carbon\CarbonImmutable;

test('isDueAt matches the configured day and time exactly', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->fresh()->isDueAt(CarbonImmutable::parse('2026-08-14 17:00')))->toBeTrue(); // Friday
});

test('isDueAt is false on the wrong day', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->isDueAt(CarbonImmutable::parse('2026-08-13 17:00')))->toBeFalse(); // Thursday
});

test('isDueAt is false at the wrong time', function () {
    $setting = ReportSetting::current();
    $setting->update(['send_day' => 5, 'send_time' => '17:00']);

    expect($setting->isDueAt(CarbonImmutable::parse('2026-08-14 17:01')))->toBeFalse();
});
