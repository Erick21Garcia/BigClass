<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { background: #1e3a5f; color: white; padding: 14px 20px; }
        .header h1 { font-size: 14px; }
        .header p { font-size: 10px; opacity: 0.85; }
        .title { text-align:center; font-size:13px; font-weight:bold; margin-top:4px; }
        .meta { padding: 10px 20px; display: table; width: 100%; border-bottom: 1px solid #e5e7eb; }
        .meta-col { display: table-cell; width: 33%; }
        .field { margin-bottom: 5px; }
        .field label { font-size: 8px; text-transform: uppercase; color: #666; }
        .field span { font-weight: bold; display: block; }
        table { width: calc(100% - 40px); margin: 10px 20px; border-collapse: collapse; }
        th { background: #1e3a5f; color: white; padding: 5px 4px; font-size: 9px; text-align: center; }
        th.left { text-align: left; padding-left: 6px; }
        td { padding: 4px; border: 1px solid #d1d5db; font-size: 9px; text-align: center; }
        td.left { text-align: left; padding-left: 6px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .aprobado { color: #166534; font-weight: bold; }
        .reprobado { color: #991b1b; font-weight: bold; }
        .signature { display: table; width: calc(100% - 40px); margin: 30px 20px 0; }
        .sig-col { display: table-cell; width: 33%; text-align: center; padding-top: 8px; }
        .sig-line { border-top: 1px solid #1a1a1a; padding-top: 4px; font-size: 9px; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $section->curriculum->semester->career->faculty->institution->name }}</h1>
    <p>{{ $section->curriculum->semester->career->faculty->name }}</p>
    <div class="title">ACTA DE CALIFICACIONES</div>
</div>

<div class="meta">
    <div class="meta-col">
        <div class="field"><label>Materia</label><span>{{ $section->curriculum->subject->name }}</span></div>
        <div class="field"><label>Código</label><span>{{ $section->curriculum->subject->code ?? '—' }}</span></div>
    </div>
    <div class="meta-col">
        <div class="field"><label>Docente</label><span>{{ $section->teacher->person->full_name }}</span></div>
        <div class="field"><label>Sección</label><span>{{ $section->name }}</span></div>
    </div>
    <div class="meta-col">
        <div class="field"><label>Período</label><span>{{ $period->name }}</span></div>
        <div class="field"><label>Semestre</label><span>{{ $section->curriculum->semester->name }}</span></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th style="width:80px">N° Matrícula</th>
            <th class="left">Nombre del Estudiante</th>
            @foreach($parameters as $param)
                <th>{{ $param->name }}<br><small>({{ $param->percentage }}%)</small></th>
            @endforeach
            <th>Promedio</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row['enrollment_number'] }}</td>
            <td class="left">{{ $row['full_name'] }}</td>
            @foreach($row['grades'] as $grade)
                <td>{{ $grade['score'] !== null ? number_format($grade['score'], 2) : '—' }}</td>
            @endforeach
            <td style="font-weight:bold">
                {{ $row['final_grade'] !== null ? number_format($row['final_grade'], 2) : '—' }}
            </td>
            <td class="{{ $row['status'] }}">
                {{ $row['status'] === 'aprobado' ? 'APR' : ($row['status'] === 'reprobado' ? 'REP' : 'EC') }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ 3 + count($parameters) }}" style="text-align:right; font-weight:bold; background:#f1f5f9;">
                Total estudiantes:
            </td>
            <td style="font-weight:bold; background:#f1f5f9; text-align:center;">{{ count($rows) }}</td>
            <td style="background:#f1f5f9"></td>
        </tr>
    </tfoot>
</table>

<div class="signature">
    <div class="sig-col">
        <div class="sig-line">
            Docente<br>{{ $section->teacher->person->full_name }}
        </div>
    </div>
    <div class="sig-col">
        <div class="sig-line">
            Director de Carrera
        </div>
    </div>
    <div class="sig-col">
        <div class="sig-line">
            Secretaría General<br>Fecha: {{ now()->format('d/m/Y') }}
        </div>
    </div>
</div>

<div style="padding: 16px 20px; font-size: 8px; color: #999;">
    Generado el {{ $generated }} · Documento oficial del sistema académico.
</div>

</body>
</html>