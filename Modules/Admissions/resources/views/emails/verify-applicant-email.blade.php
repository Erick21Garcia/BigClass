<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #1E3A5F; line-height: 1.6;">
    <p>Hola {{ $applicantName }},</p>
    <p>
        Confirma tu correo electrónico para poder subir tus documentos y
        enviar tu postulación.
    </p>
    <p>
        <a href="{{ $verificationUrl }}"
           style="display: inline-block; padding: 10px 18px; background: #214EA4; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Verificar mi correo
        </a>
    </p>
    <p style="font-size: 12px; color: #5B6B82;">
        El enlace es válido por {{ $validHours }} horas. Si no creaste una
        cuenta en Acadex, ignora este mensaje.
    </p>
    <p>Saludos,<br>Acadex</p>
</body>
</html>