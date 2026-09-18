<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #1E3A5F; line-height: 1.6;">
    <p>Hola {{ $applicantName }},</p>
    @if ($approved)
        <p>
            ¡Felicidades! Tu postulación ha sido <strong>aprobada</strong>.
            En los próximos días recibirás información sobre los siguientes pasos.
        </p>
    @else
        <p>
            Lamentamos informarte que tu postulación no fue aprobada en esta ocasión.
        </p>
    @endif

    @if ($notes)
        <p><em>{{ $notes }}</em></p>
    @endif

    <p>Saludos,<br>Acadex</p>
</body>
</html>