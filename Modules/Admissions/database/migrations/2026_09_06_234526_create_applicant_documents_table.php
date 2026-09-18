<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnDelete();

            // Punto 2: los 4 tipos fijos, ni uno más.
            $table->enum('type', ['cedula', 'titulo_bachiller', 'foto', 'comprobante_pago']);

            $table->string('file_path');
            $table->string('original_name');
            $table->dateTime('uploaded_at');

            $table->timestamps();

            // Un aspirante solo tiene UN archivo vigente por tipo — subir
            // de nuevo reemplaza, no acumula versiones.
            $table->unique(['applicant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_documents');
    }
};