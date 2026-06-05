<?php

namespace Modules\Academic\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BulkEnrollmentTemplateExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function array(): array
    {
        // Filas de ejemplo para que el usuario entienda el formato
        return [
            ['0912345678', 'MAT101,FIS201,QUI101'],
            ['1712345678', 'MAT101,ING201'],
            ['0987654321', 'BIO101'],
        ];
    }

    public function headings(): array
    {
        return ['cedula', 'codigos_materias'];
    }

    public function title(): string
    {
        return 'Matrícula Masiva';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 50,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Cabecera en negrita con fondo azul
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2563EB'],
            ],
        ]);

        // Filas de ejemplo en gris claro para distinguirlas
        $sheet->getStyle('A2:B4')->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF1F5F9'],
            ],
        ]);

        // Comentario en la celda B1 explicando el formato
        $comment = $sheet->getComment('B1');
        $comment->getText()->createTextRun('Ingresa los códigos de materia separados por coma. Ej: MAT101,FIS201');

        return [];
    }
}