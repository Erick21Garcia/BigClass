<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_courses', function (Blueprint $table) {
            $table->id();

            // 1 a 1 con Section del módulo Academic (ISI) — ver Punto 1 del diseño.
            $table->foreignId('section_id')
                ->unique()
                ->constrained('sections')
                ->cascadeOnDelete();

            $table->boolean('is_published')->default(false);
            $table->text('syllabus')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_courses');
    }
};