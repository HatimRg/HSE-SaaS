<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'HSE SaaS') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    
    <script>
        // Company colors from backend
        @php
            $defaultColors = [
                'primaryLight' => '#3b82f6',
                'primaryDark' => '#1d4ed8',
                'backgroundLight' => '#ffffff',
                'backgroundDark' => '#0f172a',
                'accent' => '#f59e0b'
            ];
            $companyColors = auth()->user()?->company?->getColorPalette() ?? $defaultColors;
        @endphp
        window.companyColors = @json($companyColors);
    </script>
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
