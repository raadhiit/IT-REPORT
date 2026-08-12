<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            // Tracks the last date the weekly report was triggered, so isDueAt() can use a
            // "time has passed and not sent today" window instead of an exact-minute match —
            // some shared hosting plans throttle cron to run less often than every minute.
            $table->date('last_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            $table->dropColumn('last_sent_at');
        });
    }
};
