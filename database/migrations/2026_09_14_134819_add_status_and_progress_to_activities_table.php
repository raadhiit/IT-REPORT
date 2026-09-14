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
        Schema::table('activities', function (Blueprint $table) {
            $table->enum('status', ['selesai', 'on_track', 'pending'])->default('selesai')->after('kategori');
            // Only meaningful when kategori = project; left null for every other category.
            $table->unsignedTinyInteger('progress_percent')->nullable()->after('status');
            $table->date('target_selesai')->nullable()->after('progress_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['status', 'progress_percent', 'target_selesai']);
        });
    }
};
