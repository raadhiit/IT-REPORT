<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\ReportSetting;
use App\Models\User;
use App\Services\WeeklyReportAggregator;
use App\Services\WeeklyReportExcelExporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Signature('report:send-weekly')]
#[Description('Email the current week\'s activity report (Excel) to the configured GM/SPV recipients.')]
class SendWeeklyReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WeeklyReportAggregator $aggregator, WeeklyReportExcelExporter $exporter): int
    {
        $setting = ReportSetting::current();
        $setting->markSentNow();

        if (! $setting->gm_email || ! $setting->gm_name) {
            $this->warn('Report settings has no GM email/name configured — skipping.');

            return self::SUCCESS;
        }

        $staff = User::query()
            ->where('is_active', true)
            ->whereNotNull('office_email')
            ->whereNotNull('office_email_password')
            ->whereNotNull('office_mail_host')
            ->whereNotNull('office_mail_port')
            ->whereNotNull('office_mail_encryption')
            ->get();

        if ($staff->isEmpty()) {
            $this->warn('No active staff has an office mailbox configured — skipping.');

            return self::SUCCESS;
        }

        [$start, $end] = WeeklyReportAggregator::currentWeek();
        $periodLabel = "{$start->toFormattedDateString()} – {$end->toFormattedDateString()}";

        foreach ($staff as $member) {
            $report = $aggregator->build($member, $start, $end);
            $spreadsheet = $exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), $member->name);

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
                $periodLabel,
                $excelContents,
                "laporan-mingguan-{$member->name}-{$start->toDateString()}.xlsx",
                $setting->gm_name,
                $setting->spv_email ? $setting->spv_name : null,
                $member->name,
                $member->office_email,
            ));

            $this->info("Weekly report sent to {$setting->gm_email} from {$member->office_email}.");
        }

        return self::SUCCESS;
    }
}
