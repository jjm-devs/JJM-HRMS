@props([
    'area',
    'pageName',
])

<section class="mx-auto max-w-7xl px-6 py-10">
    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-medium text-slate-500">{{ $area }} Area</p>
        <h1 class="mt-3 text-3xl font-semibold text-slate-950">This is the {{ $pageName }} page.</h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
            This page is currently a scaffold. The real {{ strtolower($pageName) }} features will be added here later.
        </p>
    </div>
</section>
