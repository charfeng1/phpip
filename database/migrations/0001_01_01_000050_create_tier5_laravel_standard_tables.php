<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 5 Laravel standard tables.
 *
 * Tables created:
 * - password_resets: Password reset tokens
 * - failed_jobs: Failed queue job records
 *
 * Note: The 'migrations' table is created automatically by Laravel
 * and should not be created manually.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createPasswordResetsTable();
        $this->createFailedJobsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_resets');
    }

    /**
     * Create the password_resets table.
     */
    private function createPasswordResetsTable(): void
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email', 255);
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();

            $table->index('email', 'idx_password_resets_email');
        });
    }

    /**
     * Create the failed_jobs table.
     */
    private function createFailedJobsTable(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 255)->unique();
            $table->text('connection');
            $table->text('queue');
            $table->text('payload');
            $table->text('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
};
