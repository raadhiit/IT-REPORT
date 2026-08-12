<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    /**
     * Number of trailing log lines to show.
     */
    private const LOG_LINES = 200;

    /**
     * Max bytes read from the end of the log file, so a large log doesn't get loaded whole.
     */
    private const LOG_MAX_BYTES = 200_000;

    /**
     * Show cron/report schedule status and the tail of the application log.
     */
    public function index(): Response
    {
        $setting = ReportSetting::current();
        $now = CarbonImmutable::now('Asia/Jakarta');

        return Inertia::render('admin/monitoring/Index', [
            'schedule' => [
                'send_day_label' => CarbonImmutable::now()
                    ->startOfWeek(CarbonImmutable::SUNDAY)
                    ->addDays($setting->send_day)
                    ->translatedFormat('l'),
                'send_time' => $setting->send_time,
                'last_sent_at' => $setting->last_sent_at?->toDateString(),
                'is_due_now' => $setting->isDueAt($now),
                'server_time' => $now->format('Y-m-d H:i:s'),
            ],
            'recentLog' => $this->tailLog(storage_path('logs/laravel.log')),
        ]);
    }

    /**
     * Read the trailing lines of a log file without loading the whole file into memory.
     */
    private function tailLog(string $path): string
    {
        if (! File::exists($path)) {
            return '';
        }

        $size = File::size($path);
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return '';
        }

        fseek($handle, max(0, $size - self::LOG_MAX_BYTES));
        $content = stream_get_contents($handle);
        fclose($handle);

        $lines = explode("\n", $content);

        return implode("\n", array_slice($lines, -self::LOG_LINES));
    }
}
