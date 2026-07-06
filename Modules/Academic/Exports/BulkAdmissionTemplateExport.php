<?php

namespace Modules\Academic\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BulkAdmissionTemplateExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function array(): array
    {
        return [
            [
                '0912345678', 'EST-2026-001', 'Juan', 'Carlos', 'Pérez', 'Gómez',
                '022345678', '0991234567', '2005-03-12', 'Quito',
                'Av. Amazonas', 'Calle 10', 'La Mariscal', 'Casa azul',
                1, 1, 1, 1, 1, 1, 1, 1,
                'MAT101,FIS201,QUI101',
            ],
            [
                '1712345678', 'EST-2026-002', 'María', '', 'López', 'Ruiz',
                '', '0987654321', '2004-11-05', 'Ibarra',
                '', '', '', '',
                2, 1, '', '', '', '', '', '',
                'MAT101,ING201',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'cedula', 'enrollment_number', 'first_name', 'second_name', 'first_surname', 'second_surname',
            'phone', 'cellphone', 'birthdate', 'place_birth',
            'main_street', 'secondary_street', 'neighborhood', 'reference',
            'sex_id', 'type_identification_id', 'marital_status_id', 'nationality_id',
            'education_level_id', 'countries_id', 'provinces_id', 'cities_id',
            'codigos_materias',
        ];
    }

    public function title(): string
    {
        return 'Alta Masiva';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 16, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14,
            'G' => 12, 'H' => 12, 'I' => 12, 'J' => 14,
            'K' => 16, 'L' => 16, 'M' => 16, 'N' => 16,
            'O' => 9, 'P' => 16, 'Q' => 14, 'R' => 14,
            'S' => 16, 'T' => 12, 'U' => 12, 'V' => 12,
            'W' => 28,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:W1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
        ]);

        $sheet->getStyle('A2:W3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
        ]);

        $sheet->getComment('O1')->getText()->createTextRun('ID numérico de la tabla sexes');
        $sheet->getComment('P1')->getText()->createTextRun('ID numérico de la tabla type_identifications');
        $sheet->getComment('W1')->getText()->createTextRun('Códigos de materia separados por coma. Ej: MAT101,FIS201');

        return [];
    }
}