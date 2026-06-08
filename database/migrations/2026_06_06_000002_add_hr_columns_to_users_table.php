<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('is_active');
            $table->unsignedBigInteger('supervisor_id')->nullable()->after('division_id');

            $table->foreign('division_id')
                ->references('id')->on('divisions')
                ->nullOnDelete();

            $table->foreign('supervisor_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index('division_id');
            $table->index('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex(['division_id']);
            $table->dropIndex(['supervisor_id']);
            $table->dropColumn(['division_id', 'supervisor_id']);
        });
    }
};
