<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_parameters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_period_id')
                  ->constrained('academic_periods')
                  ->cascadeOnDelete();

            $table->foreignId('curriculum_id')
                  ->nullable()
                  ->constrained('curricula')
                  ->nullOnDelete();

            $table->string('name');

            $table->decimal('percentage', 5, 2);

            $table->boolean('is_final')->default(false);

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(
                ['academic_period_id', 'curriculum_id', 'name'],
                'eval_params_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_parameters');
    }
};