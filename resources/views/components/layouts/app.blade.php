@props([
    'showNav' => true,
    'title'   => 'JJM Brain HRMS',
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
    <body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">

        @if ($showNav && auth()->check())
            <div class="flex min-h-screen">
                {{-- Left Sidebar --}}
                <x-navigation.main />

                {{-- Main content --}}
                <div class="flex flex-1 flex-col min-w-0">
                    {{-- Top bar --}}
                    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
                        <h1 class="text-sm font-semibold text-slate-700">{{ $title ?? 'JJM Brain HRMS' }}</h1>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-500">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </header>

                    {{-- Page content --}}
                    <main class="flex-1 p-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            {{ $slot }}
        @endif

        @livewireScripts
    </body>
</html>
