<?php

namespace App\Services;

use App\Enums\WeeklyReportLogStatus;
use App\Mail\WeeklyReportMail;
use App\Models\ReportSetting;
use App\Models\User;
use App\Models\WeeklyReportLog;
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
     * mailbox to the configured GM/SPV recipients, logging the outcome either way.
     */
    public function send(User $member, ReportSetting $setting, CarbonImmutable $start, CarbonImmutable $end): WeeklyReportLog
    {
        try {
            $report = $this->aggregator->build($member, $start, $end);
            $spreadsheet = $this->exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), $member->name);

            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            ob_start();
            $writer->save('php://output');
            $excelContents = (string) ob_get_clean();

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
                $excelContents,
                "laporan-mingguan-{$member->name}-{$start->toDateString()}.xlsx",
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

            $excelPath = "weekly-report-logs/{$log->id}.xlsx";
            Storage::disk('local')->put($excelPath, $excelContents);
            $log->update(['excel_path' => $excelPath]);

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
}
