<?php

namespace App\Services;

use App\Enums\ReportFormat;
use App\Enums\WeeklyReportLogStatus;
use App\Mail\WeeklyReportMail;
use App\Models\ReportSetting;
use App\Models\User;
use App\Models\WeeklyReportLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class WeeklyReportSender
{
    public function __construct(
        private readonly WeeklyReportAggregator $aggregator,
        private readonly WeeklyReportExcelExporter $exporter,
    ) {}

    /**
     * Build and email the given staff member's report for the period, from their own office
     * mailbox to the configured GM/SPV recipients, logging the outcome either way. Sent as
     * Excel or PDF depending on the staff member's own report_format preference.
     *
     * Staff with zero logged activity in the period are skipped (logged, not emailed) — an empty
     * report tells the GM nothing useful, and sending it every time a staff member has nothing to
     * report would just be noise.
     */
    public function send(User $member, ReportSetting $setting, CarbonImmutable $start, CarbonImmutable $end): WeeklyReportLog
    {
        try {
            $report = $this->aggregator->build($member, $start, $end);

            if ($report['total'] === 0) {
                return WeeklyReportLog::create([
                    'user_id' => $member->id,
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => WeeklyReportLogStatus::Skipped,
                    'recipient_email' => $setting->gm_email,
                    'error_message' => 'Tidak ada aktivitas tercatat pada periode ini.',
                ]);
            }

            $file = $this->buildFile($member, $report, $start, $end);

            $mailerName = "office-smtp-{$member->id}";
            config(["mail.mailers.{$mailerName}" => [
                'transport' => 'smtp',
                'host' => $member->office_mail_host,
                'port' => $member->office_mail_port,
                'encryption' => $member->office_mail_encryption,
                'username' => $member->office_email,
                'password' => $member->office_email_password,
            ]]);

            $mail = Mail::mailer($mailerName)->to($setting->gm_email);

            if ($setting->spv_email) {
                $mail->cc($setting->spv_email);
            }

            $mail->send(new WeeklyReportMail(
                "{$start->toFormattedDateString()} – {$end->toFormattedDateString()}",
                $file['contents'],
                "laporan-mingguan-{$member->name}-{$start->toDateString()}.{$file['extension']}",
                $file['mime'],
                $setting->gm_name,
                $setting->spv_email ? $setting->spv_name : null,
                $member->name,
                $member->office_email,
            ));

            $log = WeeklyReportLog::create([
                'user_id' => $member->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => WeeklyReportLogStatus::Sent,
                'recipient_email' => $setting->gm_email,
            ]);

            $filePath = "weekly-report-logs/{$log->id}.{$file['extension']}";
            Storage::disk('local')->put($filePath, $file['contents']);
            $log->update(['excel_path' => $filePath]);

            return $log;
        } catch (Throwable $e) {
            $log = WeeklyReportLog::create([
                'user_id' => $member->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => WeeklyReportLogStatus::Failed,
                'recipient_email' => $setting->gm_email,
                'error_message' => $e->getMessage(),
            ]);

            report($e);

            return $log;
        }
    }

    /**
     * Render the member's already-built report in their preferred format.
     *
     * @param  array{
     *     total: int,
     *     byCategory: Collection<int, array{value: string, label: string, count: int}>,
     *     byStatus: Collection<int, array{value: string, label: string, count: int}>,
     *     byStaff: Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>, byStatus: array<string, int>}>,
     *     detailsByCategory: Collection<int, array{value: string, label: string, activities: Collection<int, array{id: int, tanggal: string, deskripsi: string, staff: string, status: string}>}>,
     *     projects: array<int, array{id: int, deskripsi: string, staff: string, progress_percent: int|null, target_selesai: string|null, status: string}>,
     * }  $report
     * @return array{contents: string, extension: string, mime: string}
     */
    private function buildFile(User $member, array $report, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($member->report_format === ReportFormat::Pdf) {
            $pdf = Pdf::loadView('reports.weekly-pdf', [
                'start' => $start->toFormattedDateString(),
                'end' => $end->toFormattedDateString(),
                'generatedAt' => CarbonImmutable::now()->toFormattedDateString(),
                'generatedBy' => $member->name,
                'dailyCounts' => $this->aggregator->dailyCounts($member, $start, $end),
                ...$report,
            ]);

            return [
                'contents' => $pdf->output(),
                'extension' => 'pdf',
                'mime' => 'application/pdf',
            ];
        }

        $spreadsheet = $this->exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), $member->name);

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        ob_start();
        $writer->save('php://output');

        return [
            'contents' => (string) ob_get_clean(),
            'extension' => 'xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }
}
