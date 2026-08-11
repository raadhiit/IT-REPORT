<?php

use App\Models\ReportSetting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('report:send-weekly')
    ->everyMinute()
    ->when(fn () => ReportSetting::current()->isDueAt(CarbonImmutable::now('Asia/Jakarta')));
