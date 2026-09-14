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
            // Which file format this staff member's own weekly report is emailed as — set by the
            // staff member themselves (settings/profile), not an admin-only option.
            $table->enum('report_format', ['excel', 'pdf'])->default('excel')->after('office_mail_encryption');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('report_format');
        });
    }
};
