<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_attendance';

    public function up(): void
    {
        Schema::connection('pgsql_attendance')->table('attendance_logs', function (Blueprint $table) {
            $table->string('approval_status', 20)->nullable()->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
