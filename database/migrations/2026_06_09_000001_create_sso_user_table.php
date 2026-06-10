<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sso';

    /**
     * SSO authentication table (shared MySQL database, separate from Laravel `users`).
     */
    public function up(): void
    {
        Schema::connection('sso')->create('User', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('passwordHash');
            $table->string('role')->default('user');
            $table->string('avatarUrl')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::connection('sso')->dropIfExists('User');
    }
};
