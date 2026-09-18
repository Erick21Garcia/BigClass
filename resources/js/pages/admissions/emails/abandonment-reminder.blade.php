<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #1E3A5F; line-height: 1.6;">
    <p>Hola {{ $applicantName }},</p>
    <p>
        Notamos que tu postulación lleva un tiempo sin actividad.
        Si no completas y envías tu postulación en los próximos
        <strong>{{ $daysRemaining }} día(s)</strong>, se cerrará automáticamente
        por inactividad.
    </p>
    <p>Saludos,<br>Acadex</p>
</body>
</html>