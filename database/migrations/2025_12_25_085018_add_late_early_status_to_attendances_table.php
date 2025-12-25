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
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('late_minutes')->default(0)->after('is_late');
            $table->unsignedInteger('early_exit_minutes')->default(0)->after('late_minutes');
            $table->enum('status', ['present', 'absent', 'weekend', 'sick_leave', 'casual_leave'])
                  ->default('present')
                  ->after('early_exit_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'early_exit_minutes', 'status']);
        });
    }
};
