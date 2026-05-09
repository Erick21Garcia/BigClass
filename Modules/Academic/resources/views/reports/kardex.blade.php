<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { background: #1e3a5f; color: white; padding: 16px 20px; text-align: center; }
        .header h1 { font-size: 16px; }
        .header p { font-size: 10px; opacity: 0.85; margin-top: 2px; }
        .title { font-size: 14px; font-weight: bold; margin-top: 6px; }
        .student-info { padding: 12px 20px; display: table; width: 100%;
            border-bottom: 2px solid #1e3a5f; background: #f8fafc; }
        .si-col { display: table-cell; width: 50%; }
        .field { margin-bottom: 5px; }
        .field label { font-size: 8px; text-transform: uppercase; color: #666; }
        .field span { font-weight: bold; display: block; font-size: 11px; }
        .period-block { padding: 10px 20px; page-break-inside: avoid; }
        .period-header { background: #e8f0fe; padding: 6px 10px; margin-bottom: 4px;
            font-weight: bold; font-size: 10px; color: #1e3a5f;
            display: table; width: 100%; }
        .ph-left { display: table-cell; }
        .ph-right { display: table-cell; text-align: right; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #334155; color: white; padding: 4px 6px; font-size: 9px; text-align: left; }
        th.center { text-align: center; }
        td { padding: 3px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        td.center { text-align: center; }
        .aprobado { color: #166534; font-weight: bold; }
        .reprobado { color: #991b1b; font-weight: bold; }
        .en_curso { color: #92400e; }
        .summary { background: #f1f5f9; padding: 10px 20px; margin: 10px 20px;
            border-left: 4px solid #1e3a5f; }
        .summary-grid { display: table; width: 100%; }
        .summary-col { display: table-cell; text-align: center; }
        .summary-val { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .summary-label { font-size: 8px; text-transform: uppercase; color: #666; }
        .career-title { background: #1e3a5f; color: white; padding: 8px 20px;
            font-weight: bold; font-size: 11px; margin-top: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; padding: 6px 20px;
            border-top: 1px solid #e5e7eb; font-size: 8px; color: #999;
            display: table; }
        .fl { display: table-cell; }
        .fr { display: table-cell; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    @if($grouped->isNotEmpty())
        <h1>{{ $grouped->first()['institution'] }}</h1>
    @endif
    <div class="title">CERTIFICADO ACADÉMICO — KARDEX</div>
    <p>Registro histórico de calificaciones</p>
</div>

<div class="student-info">
    <div class="si-col">
        <div class="field">
            <label>Estudiante</label>
            <span>{{ $student->person->full_name }}</span>
        </div>
        <div class="field">
            <label>N° de Matrícula</label>
            <span>{{ $student->enrollment_number }}</span>
        </div>
    </div>
    <div class="si-col">
        <div class="field">
            <label>Identificación</label>
            <span>{{ $student->person->identification_number }}</span>
        </div>
        <div class="field">
            <label>Fecha de emisión</label>
            <span>{{ now()->format('d/m/Y') }}</span>
        </div>
    </div>
</div>

@foreach($grouped as $careerData)

<div class="career-title">
    {{ $careerData['career'] }} · {{ $careerData['faculty'] }}
</div>

@foreach($careerData['periods'] as $periodData)
<div class="period-block">
    <div class="period-header">
        <div class="ph-left">
            {{ $periodData['period'] }} · {{ $periodData['semester'] }}
        </div>
        <div class="ph-right">
            Aprobadas: {{ $periodData['approved'] }}/{{ $periodData['total'] }}
            @if($periodData['period_average'])
                · Promedio: {{ number_format($periodData['period_average'], 2) }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Código</th>
                <th class="center">Créditos</th>
                <th class="center">Calificación</th>
                <th class="center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periodData['items'] as $item)
            <tr>
                <td>{{ $item['subject'] }}</td>
                <td>{{ $item['code'] ?? '—' }}</td>
                <td class="center">{{ $item['credits'] }}</td>
                <td class="center" style="font-weight:bold">
                    {{ $item['final_grade'] !== null ? number_format($item['final_grade'], 2) : '—' }}
                </td>
                <td class="center {{ $item['status'] }}">
                    {{ $item['status'] === 'aprobado' ? 'Aprobado'
                        : ($item['status'] === 'reprobado' ? 'Reprobado' : 'En curso') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

<div class="summary">
    <div class="summary-grid">
        <div class="summary-col">
            <div class="summary-val">{{ $careerData['total_credits'] }}</div>
            <div class="summary-label">Créditos aprobados</div>
        </div>
        <div class="summary-col">
            <div class="summary-val">
                {{ $careerData['overall_average'] ? number_format($careerData['overall_average'], 2) : '—' }}
            </div>
            <div class="summary-label">Promedio general</div>
        </div>
    </div>
</div>

@endforeach

<div style="padding: 20px; text-align: center; font-size: 9px; color: #666; margin-top: 10px;">
    <p>Este documento es emitido por el sistema académico institucional.</p>
    <p>Generado el {{ $generated }} · Válido sin firma al ser descargado del sistema oficial.</p>
</div>

<div class="footer">
    <div class="fl">{{ $student->person->full_name }} · Kardex Académico</div>
    <div class="fr">Generado: {{ $generated }}</div>
</div>

</body>
</html>