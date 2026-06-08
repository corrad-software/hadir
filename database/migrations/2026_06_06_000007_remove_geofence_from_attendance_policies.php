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
            $table->dropColumn([
                'office_latitude',
                'office_longitude',
                'office_radius_meters',
                'office_location_name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->table('attendance_policies', function (Blueprint $table) {
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->unsignedInteger('office_radius_meters')->default(200);
            $table->string('office_location_name')->nullable();
        });
    }
};
