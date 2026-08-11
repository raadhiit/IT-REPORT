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

        if (! $setting->gm_email || ! $setting->gm_name) {
            $this->warn('Report settings has no GM email/name configured — skipping.');

            return self::SUCCESS;
        }

        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->error('No admin user found to generate the team-wide report.');

            return self::FAILURE;
        }

        [$start, $end] = WeeklyReportAggregator::currentWeek();
        $periodLabel = "{$start->toFormattedDateString()} – {$end->toFormattedDateString()}";

        $report = $aggregator->build($admin, $start, $end);
        $spreadsheet = $exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), 'Tim IT');

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        ob_start();
        $writer->save('php://output');
        $excelContents = (string) ob_get_clean();

        $mail = Mail::to($setting->gm_email);

        if ($setting->spv_email) {
            $mail->cc($setting->spv_email);
        }

        $mail->send(new WeeklyReportMail(
            $periodLabel,
            $excelContents,
            "laporan-mingguan-{$start->toDateString()}.xlsx",
            $setting->gm_name,
            $setting->spv_email ? $setting->spv_name : null,
            'Tim IT',
        ));

        $this->info("Weekly report sent to {$setting->gm_email}.");

        return self::SUCCESS;
    }
}
