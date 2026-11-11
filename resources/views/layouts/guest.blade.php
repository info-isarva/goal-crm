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
{{-- Bootstrap 5 & DataTables CSS --}}
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body >
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

             {{-- jQuery, Bootstrap, and DataTables JS --}}
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> --}}
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

            @yield('scripts')
        </main>
    </div>
    </body>
</html>
