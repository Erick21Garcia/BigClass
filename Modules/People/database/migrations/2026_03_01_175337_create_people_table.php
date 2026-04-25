<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Datos Personales
            |--------------------------------------------------------------------------
            */
            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->string('first_surname');
            $table->string('second_surname')->nullable();
            $table->string('identification_number')->unique();
            $table->string('phone')->nullable();
            $table->string('cellphone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('place_birth')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dirección
            |--------------------------------------------------------------------------
            */
            $table->string('main_street')->nullable();
            $table->string('secondary_street')->nullable();
            $table->string('neighborhood')->nullable();
            $table->text('reference')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Relaciones
            |--------------------------------------------------------------------------
            */
            $table->foreignId('marital_status_id')
                ->constrained('marital_statuses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('type_identification_id')
                ->constrained('type_identifications')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('sex_id')
                ->constrained('sexes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('nationality_id')
                ->constrained('nationalities')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('education_level_id')
                ->constrained('education_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('countries_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('provinces_id')
                ->constrained('provinces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('cities_id')
                ->constrained('cities')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
