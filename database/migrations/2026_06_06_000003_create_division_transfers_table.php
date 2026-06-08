<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('from_division_id')->nullable();
            $table->unsignedBigInteger('to_division_id');
            $table->date('effective_date');
            $table->boolean('processed')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('to_division_id')
                ->references('id')->on('divisions')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['user_id', 'processed']);
            $table->index(['effective_date', 'processed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_transfers');
    }
};
