<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('is_active');
            $table->string('phone', 30)->nullable()->after('dob');
            $table->string('sex', 10)->nullable()->after('phone');
            $table->string('job_title', 100)->nullable()->after('sex');
            $table->string('job_status', 30)->nullable()->after('job_title');
            $table->text('home_address')->nullable()->after('job_status');
            $table->unsignedBigInteger('office_id')->nullable()->after('home_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dob', 'phone', 'sex', 'job_title', 'job_status', 'home_address', 'office_id']);
        });
    }
};
