<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions_settings', function (Blueprint $table) {
            $table->id();

            // Punto 1 (higiene de datos): ambos configurables desde el
            // panel administrativo, sin hardcodear. Tabla de una sola
            // fila (singleton) — más simple que key-value para solo 2
            // valores.
            $table->unsignedInteger('abandon_after_days')->default(30);
            $table->unsignedInteger('retention_after_months')->default(3);

            $table->timestamps();
        });

        // Sembramos la única fila que va a existir siempre.
        \Illuminate\Support\Facades\DB::table('admissions_settings')->insert([
            'abandon_after_days'      => 30,
            'retention_after_months'  => 3,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions_settings');
    }
};