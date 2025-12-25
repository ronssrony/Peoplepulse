<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->unsignedInteger('gross_minutes')->nullable();
            $table->unsignedInteger('break_minutes')->default(60);
            $table->unsignedInteger('net_minutes')->nullable();
            $table->boolean('is_late')->default(false);
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            $table->text('clock_in_user_agent')->nullable();
            $table->text('clock_out_user_agent')->nullable();
            $table->timestamps();

            // Ensure only one attendance record per user per day
            $table->unique(['user_id', 'date']);
            
            // Index for querying by date range
            $table->index('date');
            $table->index('is_late');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
