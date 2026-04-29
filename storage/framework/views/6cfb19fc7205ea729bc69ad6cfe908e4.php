<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title><?php echo e(config('app.name', 'HSE SaaS')); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.tsx']); ?>
    
    <script>
        // Company colors from backend
        <?php
            $defaultColors = [
                'primaryLight' => '#3b82f6',
                'primaryDark' => '#1d4ed8',
                'backgroundLight' => '#ffffff',
                'backgroundDark' => '#0f172a',
                'accent' => '#f59e0b'
            ];
            $companyColors = auth()->user()?->company?->getColorPalette() ?? $defaultColors;
        ?>
        window.companyColors = <?php echo json_encode($companyColors, 15, 512) ?>;
    </script>
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Webapp\resources\views/app.blade.php ENDPATH**/ ?>