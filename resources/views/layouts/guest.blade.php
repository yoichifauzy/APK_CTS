<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CTM') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-indigo-600 via-sky-500 to-cyan-400 min-h-screen">
        <div class="min-h-screen flex flex-col items-center justify-center py-10">
            <div class="text-center text-white">
                <a href="/" class="inline-flex items-center gap-2 text-2xl font-extrabold tracking-tight">
                    <i class="fa-solid fa-cloud"></i>
                    <span>CTM</span>
                </a>
                <p class="mt-1 text-white/80 text-sm">Cloud Ticketing Manufacturing</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white/95 backdrop-blur border border-white/40 shadow-2xl rounded-2xl">
                {{ $slot }}
            </div>

            <div class="mt-6 text-white/80 text-sm">
                &copy; {{ date('Y') }} CTM · <a href="/" class="underline hover:text-white">Beranda</a>
            </div>
        </div>
    </body>
</html>
