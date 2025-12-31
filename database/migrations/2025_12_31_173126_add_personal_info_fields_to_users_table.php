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
            $table->string('nid_number')->nullable()->after('designation');
            $table->date('joining_date')->nullable()->after('nid_number');
            $table->date('closing_date')->nullable()->after('joining_date');
            $table->text('permanent_address')->nullable()->after('closing_date');
            $table->text('present_address')->nullable()->after('permanent_address');
            $table->string('nationality')->nullable()->after('present_address');
            $table->string('fathers_name')->nullable()->after('nationality');
            $table->string('mothers_name')->nullable()->after('fathers_name');
            $table->string('graduated_institution')->nullable()->after('mothers_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nid_number',
                'joining_date',
                'closing_date',
                'permanent_address',
                'present_address',
                'nationality',
                'fathers_name',
                'mothers_name',
                'graduated_institution',
            ]);
        });
    }
};
