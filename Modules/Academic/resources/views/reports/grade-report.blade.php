<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { background: #1e3a5f; color: white; padding: 16px 20px; }
        .header h1 { font-size: 15px; }
        .header p { font-size: 10px; opacity: 0.85; margin-top: 2px; }
        .title { text-align: center; font-size: 13px; font-weight: bold; margin-top: 6px; }
        .info { padding: 12px 20px; display: table; width: 100%; }
        .info-col { display: table-cell; width: 50%; }
        .field { margin-bottom: 6px; }
        .field label { font-size: 8px; text-transform: uppercase; color: #666; }
        .field span { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 0 20px; width: calc(100% - 40px); }
        th { background: #1e3a5f; color: white; padding: 5px 6px; font-size: 9px; text-align: center; }
        th.left { text-align: left; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .aprobado { color: #166534; font-weight: bold; }
        .reprobado { color: #991b1b; font-weight: bold; }
        .en_curso { color: #92400e; }
        .footer { position: fixed; bottom: 0; width: 100%; padding: 8px 20px;
            border-top: 1px solid #e5e7eb; font-size: 8px; color: #999;
            display: table; }
        .footer-l { display: table-cell; }
        .footer-r { display: table-cell; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $enrollment->career->faculty->institution->name }}</h1>
    <p>{{ $enrollment->career->faculty->name }} · {{ $enrollment->career->name }}</p>
    <div class="title">BOLETÍN DE CALIFICACIONES</div>
</div>

<div class="info">
    <div class="info-col">
        <div class="field"><label>Estudiante</label><br><span>{{ $enrollment->student->person->full_name }}</span></div>
        <div class="field"><label>N° Matrícula</label><br><span>{{ $enrollment->student->enrollment_number }}</span></div>
    </div>
    <div class="info-col">
        <div class="field"><label>Período académico</label><br><span>{{ $enrollment->academicPeriod->name }}</span></div>
        <div class="field"><label>Semestre</label><br><span>{{ $enrollment->semester->name }}</span></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="left">Materia</th>
            <th>Cód.</th>
            <th>Créd.</th>
            @foreach($parameters as $param)
                <th>{{ $param->name }}<br><span style="font-weight:normal">({{ $param->percentage }}%)</span></th>
            @endforeach
            <th>Promedio</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item['subject'] }}</td>
            <td style="text-align:center">{{ $item['code'] ?? '—' }}</td>
            <td style="text-align:center">{{ $item['credits'] }}</td>
            @foreach($item['grades'] as $grade)
                <td style="text-align:center">
                    {{ $grade['score'] !== null ? number_format($grade['score'], 2) : '—' }}
                </td>
            @endforeach
            <td style="text-align:center; font-weight:bold;">
                {{ $item['final_grade'] !== null ? number_format($item['final_grade'], 2) : '—' }}
            </td>
            <td style="text-align:center" class="{{ $item['status'] }}">
                {{ $item['status'] === 'aprobado' ? 'Aprobado' : ($item['status'] === 'reprobado' ? 'Reprobado' : 'En curso') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="padding: 16px 20px; text-align: right; font-size: 9px; color: #666;">
    Generado el {{ $generated }}
</div>

<div class="footer">
    <div class="footer-l">{{ $enrollment->career->faculty->institution->name }}</div>
    <div class="footer-r">Página <span class="page"></span></div>
</div>

</body>
</html>