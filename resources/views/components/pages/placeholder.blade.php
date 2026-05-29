@props([
    'area',
    'pageName',
])

<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        :title="$pageName"
        :description="$area . ' Area'"
    >
        <x-ui.badge variant="info">Scaffold</x-ui.badge>
    </x-ui.page-header>

    <x-ui.card :title="'This is the ' . $pageName . ' page.'">
        <p class="max-w-2xl text-sm leading-6 text-slate-600">
            This page is currently a scaffold. The real {{ strtolower($pageName) }} features will be added here later.
        </p>
    </x-ui.card>
</section>
