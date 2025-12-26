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
        Schema::table('renewals_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('job_id')->nullable()->after('id');
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renewals_logs', function (Blueprint $table) {
            $table->dropIndex(['job_id']);
            $table->dropColumn('job_id');
        });
    }
};
