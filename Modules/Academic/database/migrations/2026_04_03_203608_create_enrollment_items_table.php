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
        Schema::create('enrollment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curricula_id')->constrained('curricula')->cascadeOnDelete();
            $table->string('status');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['enrollment_id', 'curricula_id'], 'enrollment_curricula_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_items');
    }
};
