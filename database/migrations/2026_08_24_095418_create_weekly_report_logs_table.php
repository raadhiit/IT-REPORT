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
        Schema::create('weekly_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // staff whose report this attempt covers
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status'); // App\Enums\WeeklyReportLogStatus
            $table->string('recipient_email');
            $table->text('error_message')->nullable();
            $table->string('excel_path')->nullable(); // set when status is 'sent'
            $table->timestamps();

            $table->index(['user_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_report_logs');
    }
};
