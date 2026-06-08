<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('home_address');
            $table->string('address_line1', 100)->nullable()->after('job_status'); // house / unit no
            $table->string('address_line2', 150)->nullable()->after('address_line1'); // road
            $table->string('address_township', 100)->nullable()->after('address_line2');
            $table->string('address_postcode', 10)->nullable()->after('address_township');
            $table->string('address_state', 60)->nullable()->after('address_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address_line1', 'address_line2', 'address_township', 'address_postcode', 'address_state']);
            $table->text('home_address')->nullable();
        });
    }
};
