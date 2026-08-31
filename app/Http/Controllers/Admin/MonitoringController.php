<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendManualReportRequest;
use App\Models\ReportSetting;
use App\Models\User;
use App\Models\WeeklyReportLog;
use App\Services\WeeklyReportAggregator;
use App\Services\WeeklyReportSender;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        [$defaultFrom, $defaultTo] = WeeklyReportAggregator::currentWeek();

        return Inertia::render('admin/monitoring/Index', [
            'staffOptions' => User::eligibleForWeeklyReport()->orderBy('name')->get(['id', 'name']),
            'defaultFrom' => $defaultFrom->toDateString(),
            'defaultTo' => $defaultTo->toDateString(),
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
            'reportLogs' => WeeklyReportLog::query()
                ->with('user:id,name')
                ->latest()
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (WeeklyReportLog $log) => [
                    'id' => $log->id,
                    'staff' => $log->user->name,
                    'period_start' => $log->period_start->toDateString(),
                    'period_end' => $log->period_end->toDateString(),
                    'status' => $log->status->value,
                    'status_label' => $log->status->label(),
                    'recipient_email' => $log->recipient_email,
                    'error_message' => $log->error_message,
                    'has_excel' => $log->excel_path !== null,
                    'sent_at' => $log->created_at->format('Y-m-d H:i'),
                ]),
        ]);
    }

    /**
     * Manually trigger a weekly report send, bypassing the cron schedule. Admin picks which
     * staff to send for (defaults to all eligible staff) and the period to report on.
     */
    public function sendManual(SendManualReportRequest $request, WeeklyReportSender $sender): RedirectResponse
    {
        $setting = ReportSetting::current();

        if (! $setting->gm_email || ! $setting->gm_name) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Report settings has no GM email/name configured.')]);

            return back();
        }

        $staffQuery = User::eligibleForWeeklyReport();
        $userIds = $request->validated('user_ids') ?? [];

        if ($userIds !== []) {
            $staffQuery->whereIn('id', $userIds);
        }

        $staff = $staffQuery->get();

        if ($staff->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No eligible staff found for the selected recipients.')]);

            return back();
        }

        $start = CarbonImmutable::parse($request->validated('from'));
        $end = CarbonImmutable::parse($request->validated('to'));

        foreach ($staff as $member) {
            $sender->send($member, $setting, $start, $end);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Weekly report send triggered.')]);

        return back();
    }

    /**
     * Download the Excel file archived for a weekly report send attempt.
     */
    public function downloadExcel(WeeklyReportLog $weeklyReportLog): StreamedResponse
    {
        if (! $weeklyReportLog->excel_path || ! Storage::disk('local')->exists($weeklyReportLog->excel_path)) {
            abort(404);
        }

        $filename = "laporan-mingguan-{$weeklyReportLog->user->name}-{$weeklyReportLog->period_start->toDateString()}.xlsx";

        return Storage::disk('local')->download($weeklyReportLog->excel_path, $filename);
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
