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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('career_id')
                ->constrained('careers')
                ->cascadeOnDelete();

            $table->integer('number'); 
            $table->string('name'); 
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['career_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
