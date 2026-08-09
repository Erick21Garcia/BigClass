<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->cascadeOnDelete();

            // FK al ISI — con qué parámetro de evaluación se integra la nota
            // (ver Punto 2). Se valida contra evaluation_parameters existente.
            $table->foreignId('evaluation_parameter_id')
                ->constrained('evaluation_parameters')
                ->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date');
            $table->boolean('active')->default(true);

            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);

            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Un estudiante solo tiene UNA entrega por tarea (se actualiza,
            // no se duplica, si vuelve a subir archivos).
            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('assignment_submission_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_submission_id')
                ->constrained('assignment_submissions')
                ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submission_files');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};