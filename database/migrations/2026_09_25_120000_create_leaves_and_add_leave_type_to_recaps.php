<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // izin | sakit | cuti | dinas_luar
            $table->date('start_date');
            $table->date('end_date');
            $table->string('note', 180)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'end_date']);
        });

        Schema::table('attendance_recaps', function (Blueprint $table) {
            $table->string('leave_type', 20)->nullable()->after('early_leave_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_recaps', function (Blueprint $table) {
            $table->dropColumn('leave_type');
        });

        Schema::dropIfExists('leaves');
    }
};
