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
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules');
            $table->foreignId('recorded_by')->constrained('users'); // docente
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->boolean('justified')->default(false);
            $table->string('justification_note')->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }

};
