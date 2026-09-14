<?php

namespace App\Console\Commands;

use App\Enums\WeeklyReportLogStatus;
use App\Models\ReportSetting;
use App\Models\User;
use App\Services\WeeklyReportAggregator;
use App\Services\WeeklyReportSender;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('report:send-weekly')]
#[Description('Email the current week\'s activity report to the configured GM/SPV recipients, in each staff member\'s preferred format (Excel or PDF).')]
class SendWeeklyReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WeeklyReportSender $sender): int
    {
        $setting = ReportSetting::current();
        $setting->markSentNow();

        if (! $setting->gm_email || ! $setting->gm_name) {
            $this->warn('Report settings has no GM email/name configured — skipping.');

            return self::SUCCESS;
        }

        $staff = User::eligibleForWeeklyReport()->get();

        if ($staff->isEmpty()) {
            $this->warn('No active staff has an office mailbox configured — skipping.');

            return self::SUCCESS;
        }

        [$start, $end] = WeeklyReportAggregator::currentWeek();

        foreach ($staff as $member) {
            $log = $sender->send($member, $setting, $start, $end);

            if ($log->status === WeeklyReportLogStatus::Sent) {
                $this->info("Weekly report sent to {$setting->gm_email} from {$member->office_email}.");
            } else {
                $this->error("Failed to send weekly report for {$member->name}: {$log->error_message}");
            }
        }

        return self::SUCCESS;
    }
}
