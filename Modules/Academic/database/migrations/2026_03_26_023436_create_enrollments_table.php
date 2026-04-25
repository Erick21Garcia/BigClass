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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('career_id')->constrained('careers');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->foreignId('academic_period_id')->constrained('academic_periods');

            $table->date('enrollment_date');
            $table->enum('type', ['regular', 'extraordinary', 'special'])->default('regular');
            $table->enum('status', ['registered', 'active', 'withdrawn', 'completed'])->default('active');

            $table->unique(
                ['student_id', 'career_id', 'academic_period_id'],
                'unique_student_enrollment'
            );

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
