<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_attendance';

    public function up(): void
    {
        Schema::connection('pgsql_attendance')->create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->jsonb('work_days');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->unsignedSmallInteger('grace_period_minutes')->default(15);
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->unsignedInteger('office_radius_meters')->default(200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->dropIfExists('attendance_policies');
    }
};
