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
     */
    public function send(User $member, ReportSetting $setting, CarbonImmutable $start, CarbonImmutable $end): WeeklyReportLog
    {
        try {
            $file = $this->buildFile($member, $start, $end);

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
     * Render the member's report in their preferred format.
     *
     * @return array{contents: string, extension: string, mime: string}
     */
    private function buildFile(User $member, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $report = $this->aggregator->build($member, $start, $end);

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
