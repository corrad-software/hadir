<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_attendance';

    public function up(): void
    {
        Schema::connection('pgsql_attendance')->create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_log_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('corrected_check_in_at')->nullable();
            $table->timestamp('corrected_check_out_at')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->index('attendance_log_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->dropIfExists('attendance_corrections');
    }
};
