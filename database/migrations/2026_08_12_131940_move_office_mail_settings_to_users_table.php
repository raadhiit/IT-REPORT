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
        Schema::table('users', function (Blueprint $table) {
            $table->string('office_mail_host')->nullable();
            $table->unsignedSmallInteger('office_mail_port')->nullable();
            $table->string('office_mail_encryption', 10)->nullable(); // 'ssl' or 'tls'
        });

        Schema::table('report_settings', function (Blueprint $table) {
            $table->dropColumn(['office_mail_host', 'office_mail_port', 'office_mail_encryption']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['office_mail_host', 'office_mail_port', 'office_mail_encryption']);
        });

        Schema::table('report_settings', function (Blueprint $table) {
            $table->string('office_mail_host')->nullable();
            $table->unsignedSmallInteger('office_mail_port')->nullable();
            $table->string('office_mail_encryption', 10)->nullable();
        });
    }
};
