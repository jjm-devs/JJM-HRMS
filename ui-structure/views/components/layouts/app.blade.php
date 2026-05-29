@props([
    'showNav' => true,
    'title' => 'JJM Brain HRMS',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'JJM Brain HRMS' }} - {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-950 antialiased">
        @if ($showNav && auth()->check())
            <x-navigation.main />
        @endif

        {{ $slot }}

        @livewireScripts
    </body>
</html>
