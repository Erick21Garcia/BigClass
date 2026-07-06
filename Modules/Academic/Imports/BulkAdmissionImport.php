<?php

namespace Modules\Academic\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BulkAdmissionImport implements ToCollection, WithHeadingRow
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows->map(fn ($row) => [
            'cedula'                  => trim((string) ($row['cedula'] ?? '')),
            'enrollment_number'       => trim((string) ($row['enrollment_number'] ?? '')),

            'first_name'              => trim((string) ($row['first_name'] ?? '')),
            'second_name'             => $this->nullableString($row['second_name'] ?? null),
            'first_surname'           => trim((string) ($row['first_surname'] ?? '')),
            'second_surname'          => $this->nullableString($row['second_surname'] ?? null),
            'phone'                   => $this->nullableString($row['phone'] ?? null),
            'cellphone'               => $this->nullableString($row['cellphone'] ?? null),
            'birthdate'               => $this->nullableString($row['birthdate'] ?? null),
            'place_birth'             => $this->nullableString($row['place_birth'] ?? null),
            'main_street'             => $this->nullableString($row['main_street'] ?? null),
            'secondary_street'        => $this->nullableString($row['secondary_street'] ?? null),
            'neighborhood'            => $this->nullableString($row['neighborhood'] ?? null),
            'reference'               => $this->nullableString($row['reference'] ?? null),

            'sex_id'                  => $this->nullableInt($row['sex_id'] ?? null),
            'type_identification_id'  => $this->nullableInt($row['type_identification_id'] ?? null),
            'marital_status_id'       => $this->nullableInt($row['marital_status_id'] ?? null),
            'nationality_id'          => $this->nullableInt($row['nationality_id'] ?? null),
            'education_level_id'      => $this->nullableInt($row['education_level_id'] ?? null),
            'countries_id'            => $this->nullableInt($row['countries_id'] ?? null),
            'provinces_id'            => $this->nullableInt($row['provinces_id'] ?? null),
            'cities_id'               => $this->nullableInt($row['cities_id'] ?? null),

            'codigos_materias' => array_filter(
                array_map('trim', explode(',', (string) ($row['codigos_materias'] ?? '')))
            ),
        ])->filter(fn ($row) => $row['cedula'] !== '');
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function nullableInt($value): ?int
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : (int) $value;
    }
}