<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->time('time_in')->default('08:00:00');
            $table->time('time_out')->default('16:00:00');
            $table->unsignedInteger('late_tolerance_minutes')->default(0);
            $table->unsignedInteger('early_leave_tolerance_minutes')->default(0);
            $table->timestamps();
        });

        DB::table('work_schedules')->insert([
            'time_in' => '08:00:00',
            'time_out' => '16:00:00',
            'late_tolerance_minutes' => 0,
            'early_leave_tolerance_minutes' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
