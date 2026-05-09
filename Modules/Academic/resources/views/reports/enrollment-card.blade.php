<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { background: #1e3a5f; color: white; padding: 20px; text-align: center; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header p { font-size: 11px; opacity: 0.85; }
        .section { padding: 16px 24px; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase;
            color: #1e3a5f; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; margin-bottom: 12px; }
        .grid-2 { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; padding-right: 16px; }
        .field { margin-bottom: 8px; }
        .field label { font-size: 9px; text-transform: uppercase; color: #666; display: block; }
        .field span { font-size: 11px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1e3a5f; color: white; padding: 6px 8px; font-size: 10px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #166534; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 24px;
            border-top: 1px solid #e5e7eb; font-size: 9px; color: #999;
            display: table; width: 100%; }
        .footer-left { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }
        .watermark { text-align: center; padding: 16px; }
        .seal { border: 3px double #1e3a5f; display: inline-block; padding: 20px 40px;
            color: #1e3a5f; font-size: 10px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $enrollment->career->faculty->institution->name }}</h1>
    <p>{{ $enrollment->career->faculty->name }}</p>
    <p style="margin-top:6px; font-size:13px; font-weight:bold;">FICHA DE MATRÍCULA</p>
</div>

<div class="section">
    <div class="section-title">Datos del Estudiante</div>
    <div class="grid-2">
        <div class="col">
            <div class="field">
                <label>Nombre completo</label>
                <span>{{ $enrollment->student->person->full_name }}</span>
            </div>
            <div class="field">
                <label>N° de Matrícula</label>
                <span>{{ $enrollment->student->enrollment_number }}</span>
            </div>
        </div>
        <div class="col">
            <div class="field">
                <label>Carrera</label>
                <span>{{ $enrollment->career->name }}</span>
            </div>
            <div class="field">
                <label>Semestre</label>
                <span>{{ $enrollment->semester->name }}</span>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Datos de la Matrícula</div>
    <div class="grid-2">
        <div class="col">
            <div class="field">
                <label>Período académico</label>
                <span>{{ $enrollment->academicPeriod->name }}</span>
            </div>
            <div class="field">
                <label>Fecha de matrícula</label>
                <span>{{ $enrollment->enrollment_date->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="col">
            <div class="field">
                <label>Tipo</label>
                <span>{{ ucfirst($enrollment->type) }}</span>
            </div>
            <div class="field">
                <label>Estado</label>
                <span class="badge badge-active">{{ ucfirst($enrollment->status) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Materias Matriculadas</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Materia</th>
                <th style="text-align:center">Créditos</th>
                <th style="text-align:center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->curriculum->subject->code ?? '—' }}</td>
                <td>{{ $item->curriculum->subject->name }}</td>
                <td style="text-align:center">{{ $item->curriculum->subject->credits }}</td>
                <td style="text-align:center">{{ ucfirst($item->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="font-weight:bold; text-align:right;">Total de créditos:</td>
                <td style="text-align:center; font-weight:bold;">
                    {{ $items->sum('curriculum.subject.credits') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="watermark">
    <div class="seal">
        <div style="font-weight:bold; font-size:12px;">DOCUMENTO OFICIAL</div>
        <div style="margin-top:4px;">Generado el {{ $generated }}</div>
        <div style="margin-top:4px; font-size:9px; color:#666;">
            Este documento es válido sin firma cuando se obtiene del sistema.
        </div>
    </div>
</div>

<div class="footer">
    <div class="footer-left">{{ $enrollment->career->faculty->institution->name }}</div>
    <div class="footer-right">Generado: {{ $generated }}</div>
</div>

</body>
</html>