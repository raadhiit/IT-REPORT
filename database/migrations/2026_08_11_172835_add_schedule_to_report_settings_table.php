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
            $table->unsignedTinyInteger('send_day')->default(5); // Carbon dayOfWeek: 0=Sunday..6=Saturday, default Friday
            $table->string('send_time', 5)->default('17:00'); // HH:mm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            $table->dropColumn(['send_day', 'send_time']);
        });
    }
};
