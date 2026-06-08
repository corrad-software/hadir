<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_attendance';

    public function up(): void
    {
        Schema::connection('pgsql_attendance')->table('attendance_policies', function (Blueprint $table) {
            $table->string('office_location_name')->nullable()->after('office_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn('office_location_name');
        });
    }
};
