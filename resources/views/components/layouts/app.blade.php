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

        {{-- Apply saved theme before paint to avoid a flash of the wrong theme. --}}
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <title>{{ $title ?? 'JJM Brain HRMS' }} - {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">
        @if (in_array(config('app.env'), ['development', 'developement', 'local'], true))
            <div class="border-b border-yellow-300 bg-yellow-100 px-4 py-2 text-center text-sm font-semibold text-yellow-900">
                This app is in development. Any data entered here will not be available in production.
            </div>
        @endif

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
                            {{-- Theme toggle --}}
                            <button
                                type="button"
                                title="Toggle dark mode"
                                aria-label="Toggle dark mode"
                                onclick="var d = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('theme', d ? 'dark' : 'light'); } catch (e) {}"
                                class="flex h-8 w-8 items-center justify-center border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                            >
                                {{-- Moon (shown in light mode) --}}
                                <svg class="h-4 w-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                                </svg>
                                {{-- Sun (shown in dark mode) --}}
                                <svg class="h-4 w-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36 6.36l-1.42-1.42M7.05 7.05L5.64 5.64m12.72 0l-1.42 1.42M7.05 16.95l-1.41 1.41M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </button>
                            <span class="text-sm text-slate-500">{{ auth()->user()->name }}</span>
                            <a href="{{ route('password.change') }}"
                               class="border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 {{ request()->routeIs('password.change') ? 'bg-slate-100 text-slate-900' : '' }}">
                                Change Password
                            </a>
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
