<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->cascadeOnDelete();

            $table->foreignId('evaluation_parameter_id')
                ->constrained('evaluation_parameters')
                ->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('active')->default(true);

            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnDelete();

            $table->enum('type', ['multiple_choice', 'essay']);
            $table->text('question');
            $table->decimal('points', 5, 2)->default(1);
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();
        });

        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_question_id')
                ->constrained('quiz_questions')
                ->cascadeOnDelete();

            $table->string('option_text');
            $table->boolean('is_correct')->default(false);

            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->unsignedInteger('attempt_number');
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();

            $table->decimal('auto_score', 5, 2)->nullable();
            $table->decimal('manual_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();

            $table->enum('status', ['in_progress', 'pending_review', 'graded'])
                ->default('in_progress');

            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['quiz_id', 'student_id', 'attempt_number']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')
                ->constrained('quiz_attempts')
                ->cascadeOnDelete();

            $table->foreignId('quiz_question_id')
                ->constrained('quiz_questions')
                ->cascadeOnDelete();

            $table->foreignId('selected_option_id')
                ->nullable()
                ->constrained('quiz_question_options')
                ->nullOnDelete();

            $table->text('written_answer')->nullable();
            $table->decimal('points_awarded', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};