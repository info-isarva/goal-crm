<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $company = \App\Models\Company::current();
        @endphp
        <link rel="icon" href="{{ $company->favicon_url }}">
        <link rel="shortcut icon" href="{{ $company->favicon_url }}">
        <title>{{ !empty($company->name) ? $company->name : config('app.name', 'Laravel') }}</title>
     {{-- DataTables (non-Bootstrap) --}}
     <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
        <!-- Fonts: Inter (ISARVA enterprise typography) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="{{ asset('css/isarva-typography.css') }}?v={{ @filemtime(public_path('css/isarva-typography.css')) }}" rel="stylesheet">

        <!-- Tailwind (full stack via Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
          <div id="app">
        

        <main >
            {{-- If this layout is used as a Blade component (eg. <x-guest-layout>), the child content
                 will be passed as the $slot variable. Otherwise, render the traditional
                 @section/@yield content for backwards compatibility. --}}
            @if(isset($slot) && trim($slot) !== '')
                {{ $slot }}
            @else
                @yield('content')
            @endif

             {{-- jQuery + DataTables (no Bootstrap) --}}
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

            @yield('scripts')
        </main>
    </div>
    </body>
</html>
