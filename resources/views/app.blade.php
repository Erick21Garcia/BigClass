<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Detectar preferencia de dark mode del sistema y aplicarla de inmediato --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{--
            Color de fondo inline para evitar flash blanco al cargar.
            Usa los mismos valores HSL definidos en app.css:
              Claro:  --background: hsl(210 40% 98%)  → #F8FAFC (slate frío)
              Oscuro: --background: hsl(222 47% 11%)  → #0F172A (slate 900)
        --}}
        <style>
            html {
                background-color: hsl(210, 40%, 98%);
            }

            html.dark {
                background-color: hsl(222, 47%, 11%);
            }
        </style>

        <title inertia>{{ config('app.name', 'BigClass') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Inter — tipografía principal de BigClass --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;1,14..32,400&display=swap" rel="stylesheet">

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>