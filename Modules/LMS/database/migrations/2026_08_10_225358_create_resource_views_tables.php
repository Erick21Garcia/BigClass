<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_id')
                ->constrained('resources')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->dateTime('viewed_at');

            $table->timestamps();

            // Marcar dos veces como leído no crea duplicados, solo
            // actualiza la fecha.
            $table->unique(['resource_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_views');
    }
};