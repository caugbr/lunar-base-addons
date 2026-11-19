<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calculadora de Mapa Astral</title>
    <link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-public.css') }}">
</head>
<body class="astro-calc-standalone-body">
    <main class="astro-calc-standalone-main">
        @include('astrology::public.partials.calculator-content')
    </main>
    @stack('styles')
    @stack('scripts')
</body>
</html>
