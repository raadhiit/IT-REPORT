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
            $table->string('office_mail_host')->nullable(); // shared SMTP host for staff office mailboxes
            $table->unsignedSmallInteger('office_mail_port')->nullable();
            $table->string('office_mail_encryption', 10)->nullable(); // 'ssl' or 'tls'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_settings', function (Blueprint $table) {
            $table->dropColumn(['office_mail_host', 'office_mail_port', 'office_mail_encryption']);
        });
    }
};
