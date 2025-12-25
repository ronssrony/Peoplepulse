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
        Schema::table('users', function (Blueprint $table) {
            // Drop old string columns and add foreign key columns
            $table->dropColumn(['department', 'sub_department']);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn(['department_id', 'sub_department_id']);
            $table->string('department');
            $table->string('sub_department')->nullable();
        });
    }
};
