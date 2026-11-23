<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <?php
            $company = \App\Models\Company::current();
        ?>
        <link rel="icon" href="<?php echo e($company->favicon_url); ?>">
        <link rel="shortcut icon" href="<?php echo e($company->favicon_url); ?>">
        <title><?php echo e(!empty($company->name) ? $company->name : config('app.name', 'Laravel')); ?></title>
     
     <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
        <!-- Fonts: Inter (ISARVA enterprise typography) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="<?php echo e(asset('css/isarva-typography.css')); ?>?v=<?php echo e(@filemtime(public_path('css/isarva-typography.css'))); ?>" rel="stylesheet">

        <!-- Tailwind (full stack via Vite) -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased">
          <div id="app">
        

        <main >
            
            <?php if(isset($slot) && trim($slot) !== ''): ?>
                <?php echo e($slot); ?>

            <?php else: ?>
                <?php echo $__env->yieldContent('content'); ?>
            <?php endif; ?>

             
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

            <?php echo $__env->yieldContent('scripts'); ?>
        </main>
    </div>
    </body>
</html>
<?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/layouts/guest.blade.php ENDPATH**/ ?>