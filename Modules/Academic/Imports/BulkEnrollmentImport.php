<?php

namespace Modules\Academic\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Lee el Excel de matrícula masiva.
 *
 * Columnas esperadas (fila 1 = cabecera):
 *   cedula           | Cédula del estudiante
 *   codigos_materias | Códigos separados por coma  (ej: MAT101,FIS201)
 *
 * Los demás campos (período, tipo, estado, fecha) vienen del form,
 * no del Excel, para evitar inconsistencias.
 */
class BulkEnrollmentImport implements ToCollection, WithHeadingRow
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows->map(fn ($row) => [
            'cedula'           => trim((string) ($row['cedula'] ?? '')),
            'codigos_materias' => array_filter(
                array_map('trim', explode(',', (string) ($row['codigos_materias'] ?? '')))
            ),
        ])->filter(fn ($row) => $row['cedula'] !== '');
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}