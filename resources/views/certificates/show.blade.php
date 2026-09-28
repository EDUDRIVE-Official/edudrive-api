<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado EDUDRIVE — {{ $courseName }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { background: white !important; }
            .print-hidden { display: none !important; }
            .certificate-sheet { min-height: 180mm !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-background p-4 font-sans text-text sm:p-8">
    <div class="print-hidden mx-auto mb-4 flex max-w-5xl flex-wrap justify-between gap-3">
        <a href="{{ route('certificates.index') }}" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-primary">← Mis certificados</a>
        <button type="button" onclick="window.print()" class="min-h-[44px] rounded-md bg-primary px-5 py-2 text-sm font-semibold text-white">Imprimir o guardar como PDF</button>
    </div>

    <main class="certificate-sheet relative mx-auto flex min-h-[180mm] max-w-5xl flex-col items-center justify-center overflow-hidden border-[10px] border-primary bg-surface px-8 py-12 text-center shadow-xl">
        <div class="absolute inset-3 border-2 border-secondary" aria-hidden="true"></div>
        <div class="relative z-10">
            <img src="{{ asset('brand/edudrive-logo.svg') }}" alt="EDUDRIVE — Educación vial para la vida" class="mx-auto h-20 w-auto">
            <p class="mt-6 text-sm font-semibold uppercase tracking-[0.3em] text-text-secondary">Certificado de aprendizaje vial</p>
            <h1 class="mt-5 font-heading text-4xl font-bold sm:text-5xl">Misión completada</h1>
            <p class="mt-7 text-lg text-text-secondary">Se reconoce a</p>
            <p class="mt-2 border-b-2 border-secondary px-8 pb-2 font-heading text-3xl font-bold">{{ $holderName }}</p>
            <p class="mx-auto mt-7 max-w-3xl text-lg leading-8 text-text-secondary">
                por completar satisfactoriamente <strong class="text-text">{{ $courseName }}</strong>, demostrando participación, toma de decisiones y aprendizaje en las prácticas de educación vial registradas por EDUDRIVE.
            </p>

            @if ($courseObjectives)
                <div class="mx-auto mt-6 max-w-3xl rounded-lg border border-primary/20 bg-primary/5 px-5 py-4 text-left">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Propósito formativo alcanzado</p>
                    <p class="mt-1 text-sm leading-6 text-text-secondary">{{ $courseObjectives }}</p>
                </div>
            @endif

            <div class="mt-8 grid gap-4 text-sm sm:grid-cols-4">
                <div><span class="block text-text-secondary">Fecha de emisión</span><strong>{{ \Illuminate\Support\Carbon::parse($certificate['issued_at'])->format('d/m/Y') }}</strong></div>
                <div><span class="block text-text-secondary">Duración estimada</span><strong>{{ $courseDurationHours ? $courseDurationHours.' hora'.($courseDurationHours === 1 ? '' : 's') : 'Según el recorrido' }}</strong></div>
                <div><span class="block text-text-secondary">Estado</span><strong>{{ $certificate['status'] === 'revoked' ? 'Revocado' : 'Válido' }}</strong></div>
                <div><span class="block text-text-secondary">Código verificable</span><strong class="tracking-wider">{{ $certificate['validation_code'] }}</strong></div>
            </div>

            <p class="mt-8 break-all text-xs text-text-secondary">Verificación pública: {{ $verificationUrl }}</p>
            <p class="mx-auto mt-3 max-w-3xl text-[11px] leading-5 text-text-secondary">Esta constancia acredita una experiencia educativa. No sustituye licencias, permisos, evaluaciones oficiales ni requisitos legales para conducir.</p>
        </div>
    </main>
</body>
</html>
