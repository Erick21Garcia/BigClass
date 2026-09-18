<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('career_id')
                ->constrained('careers')
                ->restrictOnDelete();

            // Espejo de los campos de Person — se copian tal cual al
            // aprobar (Punto 5), por eso viven aquí duplicados a propósito
            // y no como referencia a Person (que todavía no existe).
            $table->string('first_name')->nullable();
            $table->string('second_name')->nullable();
            $table->string('first_surname')->nullable();
            $table->string('second_surname')->nullable();
            $table->string('identification_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('cellphone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('place_birth')->nullable();
            $table->string('main_street')->nullable();
            $table->string('secondary_street')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('marital_status_id')->nullable();
            $table->unsignedBigInteger('type_identification_id')->nullable();
            $table->unsignedBigInteger('sex_id')->nullable();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->unsignedBigInteger('education_level_id')->nullable();
            $table->unsignedBigInteger('countries_id')->nullable();
            $table->unsignedBigInteger('provinces_id')->nullable();
            $table->unsignedBigInteger('cities_id')->nullable();

            // Punto 1 y 4: estados del Kanban + 'borrador' antes de enviar.
            $table->enum('status', [
                'borrador', 'recibida', 'en_revision', 'aprobada', 'rechazada', 'abandonada',
            ])->default('borrador');

            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};