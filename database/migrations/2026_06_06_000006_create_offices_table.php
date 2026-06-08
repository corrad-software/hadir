<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_attendance';

    public function up(): void
    {
        Schema::connection('pgsql_attendance')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(200);
            $table->unsignedBigInteger('policy_id');
            $table->foreign('policy_id')->references('id')->on('attendance_policies')->cascadeOnDelete();
            $table->timestamps();

            $table->index('policy_id');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_attendance')->dropIfExists('offices');
    }
};
