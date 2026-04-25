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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_item_id')
                  ->constrained('enrollment_items')
                  ->cascadeOnDelete();

            $table->foreignId('evaluation_parameter_id')
                  ->constrained('evaluation_parameters');

            $table->decimal('score', 5, 2);

            $table->text('observations')->nullable();

            $table->boolean('active')->default(true);

            $table->unique(
                ['enrollment_item_id', 'evaluation_parameter_id'],
                'unique_student_grade'
            );
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
